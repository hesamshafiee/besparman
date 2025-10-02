<?php

namespace App\Http\Controllers\V1;

use App\Events\VersionUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\VersionRequest;
use App\Http\Resources\V1\VersionResource;
use App\Models\Version;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Version
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Version::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new VersionResource(Version::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(VersionResource::collection(Version::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @return JsonResponse
     * @group Version
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new VersionResource(Version::where('status', 1)->where('id', $id)->firstOrFail()));
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

        return response()->jsonMacro(VersionResource::collection(Version::where('status', 1)->orderBy($order, $typeOrder)->paginate($perPage)));
    }
    
    /**
     *
     * @param VersionRequest $request
     * @return JsonResponse
     * @group Version
     */
    public function store(VersionRequest $request): JsonResponse
    {
        $this->authorize('create', Version::class);

        $version = new Version();
        $version->fill($request->safe()->all());

        if ($version->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     *
     * @param VersionRequest $request
     * @param Version $version
     * @return JsonResponse
     * @group Version
     */
    public function update(VersionRequest $request, Version $version): JsonResponse
    {
        $this->authorize('update', Version::class);

        $version->fill($request->safe()->all());

        if ($version->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $version->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     *
     * @param Version $version
     * @return JsonResponse
     * @group Version
     */
    public function destroy(Version $version): JsonResponse
    {
        $this->authorize('delete', Version::class);

        if ($version->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $version->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Version
     */
    public function latestByType(Request $request): JsonResponse
    {
        $type = $request->query('type');

        if (!in_array($type, Version::TYPES)) {
            return response()->serverError('Invalid type. Allowed types: admin, panel');
        }

        $version = Version::where('type', $type)
            ->orderByDesc('id')
            ->firstOrFail();

        return response()->jsonMacro(new VersionResource($version));
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Version
     */
    public function updateForUsers(): JsonResponse
    {
        $this->authorize('update', Version::class);

        $version = Version::where('type', 'panel')->firstOrFail();

        event(new VersionUpdated($version));

        return response()->ok(__('general.savedSuccessfully'));
    }
}
