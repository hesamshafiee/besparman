<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PointRequest;
use App\Http\Resources\V1\PointResource;
use App\Models\Point;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Point
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Point::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PointResource(Point::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PointResource::collection(Point::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param PointRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Point
     */
    public function store(PointRequest $request): JsonResponse
    {
        $this->authorize('create', Point::class);

        $point = new Point();
        $point->fill($request->safe()->all());

        if ($point->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param PointRequest $request
     * @param Point $point
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Point
     */
    public function update(PointRequest $request, Point $point): JsonResponse
    {
        $this->authorize('update', Point::class);

        $point->fill($request->safe()->all());

        if ($point->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $point->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Point $point
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Point
     */
    public function destroy(Point $point): JsonResponse
    {
        $this->authorize('delete', Point::class);

        if ($point->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $point->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
