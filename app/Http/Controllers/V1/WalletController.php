<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Financial\WalletRequest;
use App\Http\Resources\V1\WalletTransactionResource;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\V1\Wallet\Wallet;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Wallet
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', WalletTransaction::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new WalletTransactionResource(WalletTransaction::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(WalletTransactionResource::collection(WalletTransaction::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Wallet
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new WalletTransactionResource(WalletTransaction::where('user_id', Auth::id())->where('id', $id)->firstOrFail()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(WalletTransactionResource::collection(WalletTransaction::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param WalletRequest $request
     * @return JsonResponse
     * @group Wallet
     */
    public function transfer(WalletRequest $request): JsonResponse
    {
        $user = User::where('mobile', $request->mobile)->firstOrFail();

        $response = Wallet::transfer($request->value, $user->id);

        if ($response['status']) {
            return response()->ok(__('transfer requested'));
        }

        return response()->serverError($response['error']);
    }

    /**
     * @param WalletRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Wallet
     */
    public function confirmTransfer(WalletRequest $request): JsonResponse
    {
        $this->authorize('confirmTransfer', WalletTransaction::class);

        $response = Wallet::confirmTransfer($request->transactionId);

        if ($response['status']) {
            return response()->ok(__('transferred successfully'));
        }

        return response()->serverError($response['error']);
    }

    /**
     * @param WalletRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Wallet
     */
    public function rejectTransfer(WalletRequest $request): JsonResponse
    {
        $this->authorize('rejectTransfer', WalletTransaction::class);

        $response = Wallet::rejectTransfer($request->transactionId, $request->message);

        if ($response['status']) {
            return response()->ok(__('transfer rejected'));
        }

        return response()->serverError($response['error']);
    }

    /**
     * @param WalletRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Wallet
     */
    public function increaseByAdmin(WalletRequest $request): JsonResponse
    {
        $this->authorize('increaseByAdmin', WalletTransaction::class);

        $response = Wallet::increaseByAdmin($request->value, $request->userId, $request->message);

        if ($response['status']) {
            return response()->ok(__('increased successfully'));
        }

        return response()->serverError($response['error']);
    }

    /**
     * @param WalletRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Wallet
     */
    public function decreaseByAdmin(WalletRequest $request): JsonResponse
    {
        $this->authorize('decreaseByAdmin', WalletTransaction::class);

        $response = Wallet::decreaseByAdmin($request->value, $request->userId, $request->message);

        if ($response['status']) {
            return response()->ok(__('decreased successfully'));
        }

        return response()->serverError($response['error']);
    }

    /**
     * @param string $code
     * @return JsonResponse
     * @group Wallet
     */
    public function check(string $code): JsonResponse
    {
        $transaction = WalletTransaction::where('webservice_code', Auth::id() . '-' . $code)->first();

        if (!$transaction) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction not found'
            ], status::HTTP_OK);
        }
        $status = false;
        if ($transaction->third_party_status) {
            $status = true;
            $statusMessage = 'Successful';
        } elseif (is_null($transaction->third_party_status) ) {
            $statusMessage = 'Pending';
        } else {
            $statusMessage = 'failed';
        }

        return response()->json([
            'status'  => $status,
            'message' => $statusMessage,
        ], status::HTTP_OK);
    }

    /**
     * @param string $resnumber
     * @return JsonResponse
     * @group Wallet
     */
    public function getStatus(string $resnumber): JsonResponse
    {
        if (Auth::check()) {
            $status = !! WalletTransaction::where('resnumber', $resnumber)->where('user_id', Auth::id())->first();
        } else {
            $status = !! WalletTransaction::where('resnumber', $resnumber)->where('main_page', true)->whereBetween('created_at', [
                Carbon::now()->subMinutes(30),
                Carbon::now()
            ])->first();
        }

        return response()->json([
            'status' => $status,
        ], status::HTTP_OK);
    }
}
