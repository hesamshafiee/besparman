<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SaleRequest;
use App\Http\Resources\V1\SaleResource;
use App\Models\Sale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Sale
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Sale::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new SaleResource(Sale::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(SaleResource::collection(Sale::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param SaleRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Sale
     */
    public function store(SaleRequest $request): JsonResponse
    {
        $this->authorize('create', Sale::class);

        $sale = new Sale();
        $sale->fill($request->safe()->all());

        if ($sale->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param SaleRequest $request
     * @param Sale $sale
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Sale
     */
    public function update(SaleRequest $request, Sale $sale): JsonResponse
    {
        $this->authorize('update', Sale::class);

        $sale->fill($request->safe()->all());

        if ($sale->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $sale->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Sale $sale
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Sale
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $this->authorize('delete', Sale::class);

        if ($sale->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $sale->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

}
