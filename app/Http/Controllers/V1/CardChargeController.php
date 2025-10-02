<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CardChargeResource;
use App\Models\CardCharge;
use App\Models\Product;
use App\Models\Profit;
use App\Services\V1\Cart\Cart;
use App\Services\V1\Wallet\Wallet;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CardChargeController extends Controller
{
    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group CardCharge
     */
    public function clientBuy(Request $request): JsonResponse
    {
        if (!Auth::user()->isPanelOrWebservice()) {
            return response()->serverError('You do not have access to this feature');
        }

        $validated = $request->validate([
            'taken_value' => ['required', 'string'],
            'products'    => ['required'],
        ]);

        $takenValue = $validated['taken_value'];
        $cardCharges = json_decode($request->input('products'), true);

        $validator = Validator::make($cardCharges, [
            '*.count'      => 'required|numeric',
            '*.product_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $response = DB::transaction(function () use ($cardCharges, $takenValue) {
                $cart = Cart::instance('card_charge');
                $allReservedCards = collect();

                $productIds = collect($cardCharges)->pluck('product_id')->unique()->toArray();
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                foreach ($cardCharges as $item) {
                    $productId = $item['product_id'];
                    $product = $products->get($productId);

                    if (!$product || $product->type !== Product::TYPE_CARD_CHARGE) {
                        throw new \Exception("The product with ID {$productId} does not exist or is not of type 'Card Charge'.");
                    }

                    $cart->addToCart($product, $item['count']);

                    $charges = CardCharge::where('product_id', $productId)
                        ->where('status', CardCharge::STATUS_OPEN)
                        ->orderBy('id', 'asc')
                        ->limit($item['count'])
                        ->lockForUpdate()
                        ->get();
                       
                    if ($charges->count() < $item['count']) {
                        throw new \Exception("There is not enough card charge available for product {$productId}.");
                    }

                    $allReservedCards = $allReservedCards->merge($charges);
                }

                $paymentResponse = Wallet::payCardCharge($takenValue, '', 'card_charge');

                if (!isset($paymentResponse['status']) || $paymentResponse['status'] !== true) {
                    throw new \Exception('The payment was unsuccessful.');
                }

                $orderId = $paymentResponse['order_id'] ?? null;

                $cardIdsToUpdate = $allReservedCards->pluck('id');
                if ($cardIdsToUpdate->isNotEmpty()) {
                    CardCharge::whereIn('id', $cardIdsToUpdate)->update([
                        'status' => CardCharge::STATUS_SOLD,
                        'user_id' => Auth::id(),
                        'order_id' => $orderId,
                        'saled_at' => now(),
                    ]);
                }
                Cart::instance('card_charge')->flush();
                return [
                    'status' => true,
                    'message' => 'Card charges purchased successfully',
                    'data' => CardChargeResource::collection(CardCharge::where('order_id', $orderId)->get())
                ];
            });

            Cart::instance('card_charge')->flush();

            return response()->json($response);
        } catch (\Exception $e) {
            Cart::instance('card_charge')->flush();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group CardCharge
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', CardCharge::class);

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $records = CardCharge::select(DB::raw('MAX(id) as id'))
            ->groupBy(DB::raw('file_name'))
            ->pluck('id');

        return response()->jsonMacro(
            CardChargeResource::collection(
                CardCharge::whereIn('id', $records)->orderBy($order, $typeOrder)->paginate($perPage)
            )
        );
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group CardCharge
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        $product_id = (int) $request->query('product_id', 0);
        $operator_id = (int) $request->query('operator_id', 0);
        $updated_at_start = $request->query('updated_at_start', 0);
        $updated_at_end =  $request->query('updated_at_end', 0);

        if ($id) {
            return response()->jsonMacro(
                new CardChargeResource(
                    CardCharge::where([
                        'user_id' => Auth::id(),
                        'id'      => $id,
                    ])->firstOrFail()
                )
            );
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $recordIds = CardCharge::where('user_id', Auth::id())
            ->when($product_id > 0, function ($query) use ($product_id) {
                $query->where('product_id', $product_id);
            })
            ->when($operator_id > 0, function ($query) use ($operator_id) {
                $query->where('operator_id', $operator_id);
            })
           ->when($updated_at_start && $updated_at_end, function ($query) use ($updated_at_start, $updated_at_end) {
                $start = Carbon::parse($updated_at_start)->startOfDay();
                $end = Carbon::parse($updated_at_end)->endOfDay();
                $query->whereBetween('updated_at', [$start, $end]);
            })
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('order_id')
            ->pluck('id');

            $paginated = CardCharge::where('user_id', Auth::id())
                ->whereIn('id', $recordIds)
                ->orderBy($order, $typeOrder)
                ->paginate($perPage);

            $orderCounts = CardCharge::where('user_id', Auth::id())
                ->whereIn('order_id', $paginated->pluck('order_id')->filter())
                ->select('order_id', DB::raw('COUNT(*) as count'))
                ->groupBy('order_id')
                ->pluck('count', 'order_id'); 

            $paginated->getCollection()->transform(function($record) use ($orderCounts) {
                $record->order_count = $orderCounts[$record->order_id] ?? 1;
                return $record;
            });

        return response()->jsonMacro(
            CardChargeResource::collection($paginated)
        );
    }

    /**
     *
     * @param CardCharge $cardCharge
     * @return JsonResponse
     * @group CardCharge
     */
    public function destroyOpen(CardCharge $cardCharge): JsonResponse
    {
        $this->authorize('suspension', CardCharge::class);

        $deleted = CardCharge::where([
            'status'      => 'open',
            'file_name'   => $cardCharge->file_name,
            'operator_id' => $cardCharge->operator_id,
        ])->delete();

        if ($deleted) {
            activity()
                ->causedBy($cardCharge)
                ->withProperties([
                    'file_name'   => $cardCharge->file_name,
                    'operator_id' => $cardCharge->operator_id,
                    'created_at'  => $cardCharge->created_at,
                ])->log('deleted_open_card_charges');

            return response()->ok(__('general.deletedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group CardCharge
     */
    public function findBySerial(Request $request): JsonResponse
    {
        $this->authorize('findBySerial', CardCharge::class);

        $validator = Validator::make($request->query(), [
            'serial' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid serial format.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $serial = $request->query('serial');

        $card = CardCharge::where('serial', $serial)->first();

        if (!$card) {
            return response()->json([
                'message' => 'Card not found.',
            ], 404);
        }

        return response()->jsonMacro(
            new CardChargeResource(
                CardCharge::where('serial', $serial)->first()
            )
        );
    }

    /**
     *
     * @return JsonResponse
     * @group CardCharge
     */
    public function freeReport(): JsonResponse
    {
        $this->authorize('freeReport', CardCharge::class);

        $results = CardCharge::select('products.name', DB::raw('COUNT(*) as total'))
            ->join('products', 'card_charges.product_id', '=', 'products.id')
            ->where('card_charges.status', CardCharge::STATUS_OPEN)
            ->groupBy('products.name')
            ->pluck('total', 'products.name')
            ->toArray();

        return response()->json(['data' => $results], 200);
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group CardCharge
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CardCharge::class);

        $validated = $request->validate([
            'file_name'     => ['string'],
            'operator_id'   => ['numeric'],
            'card_charges'  => ['required'],
        ]);

        $cardCharges = json_decode($request->input('card_charges'), true);
        $validator = Validator::make($cardCharges, [
            '*.pin'    => 'required|string|max:255',
            '*.serial' => 'required|string|max:255',
            '*.price'  => 'required|numeric|min:1000|max:1000000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $products = Product::where('type', Product::TYPE_CARD_CHARGE)->get();
        $productsArr = [];

        foreach ($products as $product) {
            $productsArr[$product->operator->id][$product->price] = $product->id;
        }

        $profit = Profit::where([
            'operator_id' => $validated['operator_id'],
            'type'        => Profit::TYPE_CARD_CHARGE,
        ])->first();

        $nowDate = now();
        $counter = 0;
        $cardChargesInfoArray = [];
        $uuid = Str::uuid()->toString();
        foreach ($cardCharges as $value) {
            $cardChargesInfoArray[] = [
                'file_name'   => $request->file_name,
                'pin'        => $value['pin'],
                'serial'     => $value['serial'],
                'profit'     => $profit->profit ?? 0,
                'price'      => $value['price'],
                'status'     => 'open',
                'product_id' => $productsArr[$request->operator_id][$value['price']] ?? 1,
                'operator_id' => $request->operator_id,
                'created_at' => $nowDate,
                'updated_at' => $nowDate,
            ];
            $counter++;
        }

        $flag = DB::connection('mysql')->table('card_charges')->insert($cardChargesInfoArray);

        if ($flag) {
            activity()
                ->causedBy(new CardCharge())
                ->withProperties([
                    'file_name'   => $request->file_name,
                    'operator_id' => $request->operator_id,
                    'created_at'  => $nowDate,
                    'count'       => $counter,
                ])->log('created');

            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
