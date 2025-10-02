<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Esaj\OperatorRequest;
use App\Http\Resources\V1\OperatorResource;
use App\Models\Operator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Operator
     */
    public function index(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new OperatorResource(Operator::findOrFail($id)));

        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(OperatorResource::collection(Operator::orderBy($order, $typeOrder)->paginate($perPage)));

    }

    /***
     * @param OperatorRequest $request
     * @param Operator $operator
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Operator
     */
    public function update(OperatorRequest $request, Operator $operator): JsonResponse
    {
        $this->authorize('update', Operator::class);
        $bill = $operator->setting['bill'] ?? false;
        $radinStatus = $operator->setting['radin_status'] ?? false;
        $radinLimit = $operator->setting['radin_limit'] ?? 0;
        $igapLimit = $operator->setting['igap_limit'] ?? 0;
        $igapLimitPackage = $operator->setting['igap_limit_package'] ?? 0;
        $radinLimitPackage = $operator->setting['radin_limit_package'] ?? 0;



        $operator->setting['credit_cell_internet'] = $request->credit_cell_internet ?? $operator->setting['credit_cell_internet'];
        $operator->setting['credit_td_lte_internet'] = $request->credit_td_lte_internet ?? $operator->setting['credit_td_lte_internet'];
        $operator->setting['credit_cell_direct_charge'] = $request->credit_cell_direct_charge ?? $operator->setting['credit_cell_direct_charge'];
        $operator->setting['credit_cell_amazing_direct_charge'] = $request->credit_cell_amazing_direct_charge ?? $operator->setting['credit_cell_amazing_direct_charge'];
        $operator->setting['credit_cell_internet_direct_charge'] = $request->credit_cell_internet_direct_charge ?? $operator->setting['credit_cell_internet_direct_charge'];
        $operator->setting['permanent_cell_internet'] = $request->permanent_cell_internet ?? $operator->setting['permanent_cell_internet'];
        $operator->setting['permanent_td_lte_internet'] = $request->permanent_td_lte_internet ?? $operator->setting['permanent_td_lte_internet'];
        $operator->setting['permanent_cell_direct_charge'] = $request->permanent_cell_direct_charge ?? $operator->setting['permanent_cell_direct_charge'];
        $operator->setting['permanent_cell_internet_direct_charge'] = $request->permanent_cell_internet_direct_charge ?? $operator->setting['permanent_cell_internet_direct_charge'];
        $operator->setting['bill'] = $request->bill ?? $bill;
        $operator->setting['radin_status'] = $request->radin_status ?? $radinStatus;
        $operator->setting['radin_limit'] = $request->radin_limit ?? $radinLimit;
        $operator->setting['igap_limit'] = $request->igap_limit ?? $igapLimit;
        $operator->setting['radin_limit_package'] = $request->radin_limit_package ?? $radinLimitPackage;
        $operator->setting['igap_limit_package'] = $request->igap_limit_package ?? $igapLimitPackage;
        $operator->status = $request->status ?? $operator->status;
        $operator->title = $request->title ?? $operator->title;

        if ($operator->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $operator->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
