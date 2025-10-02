<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\WarehouseRequest;
use App\Http\Resources\V1\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Warehouse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Warehouse::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new WarehouseResource(Warehouse::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(WarehouseResource::collection(Warehouse::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param WarehouseRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Warehouse
     */
    public function store(WarehouseRequest $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = new Warehouse();
        $warehouse->fill($request->safe()->all());

        if ($warehouse->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param WarehouseRequest $request
     * @param Warehouse $warehouse
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Warehouse
     */
    public function update(WarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', Warehouse::class);

        $warehouse->fill($request->safe()->all());

        if ($warehouse->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $warehouse->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Warehouse $warehouse
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Warehouse
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', Warehouse::class);

        if ($warehouse->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $warehouse->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
