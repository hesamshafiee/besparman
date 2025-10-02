<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Esaj\ProfitSplitRequest;
use App\Http\Resources\V1\ProfitSplitResource;
use App\Models\Profit;
use App\Models\ProfitSplit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitSplitController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitSplit
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', ProfitSplit::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new ProfitSplitResource(ProfitSplit::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(ProfitSplitResource::collection(ProfitSplit::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param ProfitSplitRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitSplit
     */
    public function store(ProfitSplitRequest $request): JsonResponse
    {
        $this->authorize('create', ProfitSplit::class);

        $profit = Profit::findOrFail($request->profit_id);

        if ($request->seller_profit > $profit->profit) {
            return response()->serverError('Seller Profit can not be more than profit');
        }

        $profitSplit = new ProfitSplit();
        $profitSplit->fill($request->safe()->all());

        if ($profitSplit->save()) {
            return response()->jsonMacro(new ProfitSplitResource($profitSplit));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param ProfitSplitRequest $request
     * @param ProfitSplit $profitSplit
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitSplit
     */
    public function update(ProfitSplitRequest $request, ProfitSplit $profitSplit): JsonResponse
    {
        $this->authorize('update', ProfitSplit::class);

        $profit = Profit::findOrFail($profitSplit->profit_id);


        if ($request->seller_profit > $profit->profit) {
            return response()->serverError('Seller Profit can not be more than profit');
        }

        $profitSplit->fill($request->safe()->all());

        if ($profitSplit->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $profitSplit->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param ProfitSplit $profitSplit
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitSplit
     */
    public function destroy(ProfitSplit $profitSplit): JsonResponse
    {
        $this->authorize('delete', ProfitSplit::class);

        if ($profitSplit->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $profitSplit->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
