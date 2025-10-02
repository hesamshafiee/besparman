<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LandingRequest;
use App\Http\Resources\V1\LandingResource;
use App\Models\Landing;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Landing
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Landing::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new LandingResource(Landing::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(LandingResource::collection(Landing::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Landing
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (string) $request->query('id');
        if ($id) {
            return response()->jsonMacro( new LandingResource(Landing::where('status', 1)->where('title', $id)->firstOrFail()));
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

        return response()->jsonMacro(LandingResource::collection(Landing::where('status', 1)->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param LandingRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Landing
     */
    public function store(LandingRequest $request): JsonResponse
    {
        $this->authorize('create', Landing::class);

        $landing = new Landing();
        $landing->fill($request->safe()->all());

        if ($landing->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param LandingRequest $request
     * @param Landing $landing
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Landing
     */
    public function update(LandingRequest $request, Landing $landing): JsonResponse
    {
        $this->authorize('update', Landing::class);

        $landing->fill($request->safe()->all());

        if ($landing->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $landing->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Landing $landing
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Landing
     */
    public function destroy(Landing $landing): JsonResponse
    {
        $this->authorize('delete', Landing::class);

        if ($landing->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $landing->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
