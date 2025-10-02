<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PointRequest;
use App\Http\Resources\V1\OrderResource;
use App\Http\Resources\V1\PointResource;
use App\Http\Resources\V1\WalletTransactionResource;
use App\Jobs\CheckIrancellOrdersStatus;
use App\Models\Order;
use App\Models\Point;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\V1\Esaj\EsajService;
use App\Services\V1\Esaj\Irancell;
use App\Services\V1\Financial\Financial;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReconciliationController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Reconciliation
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Wallet::class);

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(WalletTransactionResource::collection(WalletTransaction::whereNull('third_party_status')
            ->whereNull('third_party_info')
            ->where('detail', WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER)
            ->where('created_at', '<', now()->subMinutes(10))
            ->orderBy($order, $typeOrder)
            ->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Reconciliation
     */
    public function index2(Request $request): JsonResponse
    {
//        $this->authorize('show', Wallet::class);

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $data = Order::join('products', function ($join) {
            $join->on(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(orders.detail, '$.product_id'))"), '=', 'products.id');
        })
            ->where(function ($query) {
                $query->where(function ($sub) {
                    $sub->where('orders.status', Order::STATUSRESERVED)
                        ->where('orders.created_at', '<', now()->subDay());
                })->orWhere(function ($sub) {
                    $sub->where('orders.status', Order::STATUSPAID)
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(orders.detail, '$.transaction_value')) IS NOT NULL");
                });
            })
            ->select('orders.*', 'products.operator_id') // full order fields + operator
            ->orderBy('products.operator_id', 'desc')
            ->get();


        $grouped = $data->groupBy('operator_id');
        return response()->json($grouped);
    }

    /***
     * @param PointRequest $request
     * @param WalletTransaction $walletTransaction
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Reconciliation
     */
    public function fixTransaction(Request $request, WalletTransaction $walletTransaction): JsonResponse
    {
        $this->authorize('update', Wallet::class);

        $validated = $request->validate(['status' => ['required', 'boolean']]);

        $walletTransactionsCount = WalletTransaction::where('order_id', $walletTransaction->order_id)->count() ?? 100;
        $order = Order::find($walletTransaction->order_id);
        $detail = json_decode($order->detail, true);
        $productId = $detail['product_id'];
        $takenValue = $detail['takenValue'];
        $product = Product::find($productId);
        $user = User::find($walletTransaction->user_id);

        if (
            $walletTransaction->detail === WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER &&
            $walletTransaction->third_party_status === null &&
            $walletTransaction->third_party_info === null &&
            $walletTransaction->created_at->lt(Carbon::now()->subMinutes(30)) &&
        $walletTransactionsCount === 1
        ) {
            return DB::transaction(function () use ($validated, $walletTransaction, $takenValue, $order, $product, $user) {
                Financial::transactionsAfterTopUp(
                    $walletTransaction->user_id,
                    $walletTransaction->order_id,
                    [],
                    $validated['status'],
                    0 . substr($walletTransaction->charged_mobile, 2),
                    $product,
                    $takenValue
                );

                if (!$validated['status']) {
                    Financial::operatorStatusFalse($order, $user);
                    return response()->ok('Fixed successfully');
                }

                $prices = Financial::calculateProfit($product, $user->mobile, $walletTransaction->original_price, $walletTransaction->main_page);

                Financial::handleEsajProfit(
                    $prices['esaj_price'],
                    $prices['esaj_profit'],
                    $order,
                    $product,
                    $walletTransaction->main_page,
                    $walletTransaction->groupId ?? null,
                    null,
                    $validated['status']
                );
                Financial::calculateUserPoints($user, $product, $order);

                return response()->ok('Fixed successfully');
            });
        } elseif (
            $walletTransaction->detail === WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER &&
            $walletTransaction->third_party_status === null &&
            $walletTransaction->third_party_info === null &&
            $walletTransactionsCount > 1
        ) {
            return DB::transaction(function () use ($validated, $walletTransaction, $takenValue, $order, $product, $user) {
                Financial::transactionsAfterTopUp(
                    $walletTransaction->user_id,
                    $walletTransaction->order_id,
                    [],
                    $validated['status'],
                    0 . substr($walletTransaction->charged_mobile, 2),
                    $product,
                    $takenValue
                );
                return response()->ok('Fixed successfully');
            });
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     *
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     * @group Reconciliation
     */
    public function fixTransaction2(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', Wallet::class);

        $validated = $request->validate(['status' => ['required', 'boolean']]);
        $user = User::find($order->user_id);

        if ($user->wallet->value < $order->final_price) {
            return response()->serverError(__('Not enough money / e10'));
        }

        if ($validated['status']) {
            $detail = json_decode($order->detail, true);
            $productId = $detail['product_id'];
            $takenValue = $detail['takenValue'];
            $mainPage = $detail['mainPage'] ?? false;
            $mobile = $detail['mobile'];
            $product = Product::find($productId);
            $orderHasNoTransactions = $order->transactions()->count() === 0;


            if ($order->created_at->lt(Carbon::now()->subDay()) && $orderHasNoTransactions) {
                return DB::transaction(function () use ($validated, $takenValue, $order, $product, $user, $mainPage, $mobile) {
                    $prices = Financial::calculateProfit($product, $user->mobile, $order->final_price, $mainPage);
                    $this->esajPrice = $prices['esaj_price'];
                    $this->esajprofit = $prices['esaj_profit'];
                    $this->buyerPrice = $prices['buyer_price'];
                    $this->buyerProfit = $prices['buyer_profit'];

                    $buyerStatus = Financial::handleBuyerTransaction(
                        $prices['buyer_price'],
                        $prices['buyer_profit'],
                        $user,
                        $order,
                        $order->final_price,
                        $takenValue ?? null,
                        $mainPage,
                        $product,
                        $mobile,
                        null,
                        ''
                    );

                    $walletTransaction = WalletTransaction::where('user_id', $user->id)
                        ->where('order_id', $order->id)
                        ->where('detail', WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER)
                        ->where('status', WalletTransaction::STATUS_CONFIRMED)
                        ->first();

                    if($buyerStatus) {
                        $order->transaction_value = $walletTransaction->value;
                        $order->status = Order::STATUSPAID;

                        if (!$order->save()) {
                            throw new \Exception("Failed to save order");
                        }

                        $walletTransaction->description = 'reconciliation for date: ' . $order->created_at;
                        $walletTransaction->save();

                        Financial::transactionsAfterTopUp(
                            $walletTransaction->user_id,
                            $walletTransaction->order_id,
                            [],
                            $validated['status'],
                            0 . substr($walletTransaction->charged_mobile, 2),
                            $product,
                            $takenValue
                        );

                        Financial::handleEsajProfit(
                            $prices['esaj_price'],
                            $prices['esaj_profit'],
                            $order,
                            $product,
                            $walletTransaction->main_page,
                            $walletTransaction->groupId ?? null,
                            null,
                            $validated['status']
                        );
                        Financial::calculateUserPoints($user, $product, $order);

                        return response()->ok('Fixed successfully');
                    }
                });
            }
        } else {
            $order->status = Order::STATUSCANCELED;
            if ($order->save()) {
                return response()->ok('canceled successfully');
            }
        }


        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     * @return JsonResponse
     * @group Reconciliation
     */
    public function check(): JsonResponse
    {
        CheckIrancellOrdersStatus::dispatch();

        return response()->json([
            'message' => 'Job dispatched',
        ], 202);
    }
}
