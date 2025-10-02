<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\WalletTransactionExtraRequest;
use App\Http\Resources\V1\WalletTransactionExtraResource;
use App\Models\WalletTransactionExtra;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class WalletTransactionExtraController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group WalletTransactionExtra
     */
    public function index(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new WalletTransactionExtraResource(WalletTransactionExtra::where('id', $id)->where('user_id', Auth::id())->first()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(WalletTransactionExtraResource::collection(WalletTransactionExtra::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param WalletTransactionExtraRequest $request
     * @param WalletTransactionExtra $walletTransactionExtra
     * @return JsonResponse
     * @throws AuthorizationException
     * @group WalletTransactionExtra
     */
    public function update(WalletTransactionExtraRequest $request, WalletTransactionExtra $walletTransactionExtra): JsonResponse
    {
        if ($walletTransactionExtra->user_id === Auth::id()) {
            $walletTransactionExtra->fill($request->safe()->all());

            if ($walletTransactionExtra->save()) {
                return response()->ok(__('general.updatedSuccessfully', ['id' => $walletTransactionExtra->id]));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
