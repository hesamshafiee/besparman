<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LogisticRequest;
use App\Http\Resources\V1\LogisticResource;
use App\Models\Logistic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Logistic
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Logistic::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new LogisticResource(Logistic::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(LogisticResource::collection(Logistic::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Logistic
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro( new LogisticResource(Logistic::where('status', 1)->where('id', $id)->firstOrFail()));
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

        return response()->jsonMacro(LogisticResource::collection(Logistic::where('status', 1)->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param LogisticRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Logistic
     */
    public function store(LogisticRequest $request): JsonResponse
    {
        $this->authorize('create', Logistic::class);

        $logistic = new Logistic();
        $logistic->fill($request->safe()->all());

        if ($logistic->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param LogisticRequest $request
     * @param Logistic $logistic
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Logistic
     */
    public function update(LogisticRequest $request, Logistic $logistic): JsonResponse
    {
        $this->authorize('update', Logistic::class);

        $logistic->fill($request->safe()->all());

        if ($logistic->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $logistic->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Logistic $logistic
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Logistic
     */
    public function destroy(Logistic $logistic): JsonResponse
    {
        $this->authorize('delete', Logistic::class);

        if ($logistic->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $logistic->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
