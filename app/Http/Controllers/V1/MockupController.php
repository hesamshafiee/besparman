<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MockupRequest;
use App\Http\Resources\V1\MockupResource;
use App\Models\Mockup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MockupController extends Controller
{

    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            $item = Mockup::where('id', $id)
                ->where('is_default', 1)
                ->firstOrFail();

            return response()->jsonMacro(new MockupResource($item));
        }

        $order   = $request->query('order', 'id');
        $type    = strtolower($request->query('type_order', 'desc'));
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at', 'sort'];
        if (! in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (! in_array($type, ['asc', 'desc'], true)) {
            $type = 'desc';
        }

        $q = Mockup::where('is_default', 1);

        if ($request->filled('variant_id')) {
            $q->where('variant_id', (int) $request->query('variant_id'));
        }

        if ($request->filled('active')) {
            $q->where('is_active', (int) $request->query('active') ? 1 : 0);
        }

        $paginator = $q->orderBy($order, $type)->paginate($perPage);

        return response()->jsonMacro(
            MockupResource::collection($paginator)
        );
    }



    /**
     * لیست موکاپ‌ها
     *
     * @group Mockup
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Mockup::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(
                new MockupResource(Mockup::findOrFail($id))
            );
        }

        $order   = $request->query('order', 'id');
        $type    = strtolower($request->query('type_order', 'desc'));
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at', 'sort'];
        if (! in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (! in_array($type, ['asc', 'desc'], true)) {
            $type = 'desc';
        }

        $q = Mockup::query();

        if ($request->filled('variant_id')) {
            $q->where('variant_id', (int) $request->query('variant_id'));
        }

        if ($request->filled('active')) {
            $q->where('is_active', (int) $request->query('active') ? 1 : 0);
        }

        $paginator = $q->orderBy($order, $type)->paginate($perPage);

        return response()->jsonMacro(
            MockupResource::collection($paginator)
        );
    }

    /**
     * ساخت موکاپ جدید
     *
     * @group Mockup
     * @throws AuthorizationException
     */
    public function store(MockupRequest $request): JsonResponse
    {
        $this->authorize('create', Mockup::class);

        $data   = $request->validated();
        $mockup = new Mockup($data);

        if (empty($mockup->slug)) {
            $mockup->slug = str($mockup->name)->slug('-') . '-' . str()->random(4);
        }

        if ($mockup->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * به‌روزرسانی موکاپ
     *
     * @group Mockup
     * @throws AuthorizationException
     */
    public function update(MockupRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Mockup::class);

        $mockup = Mockup::findOrFail($id);
        $data   = $request->validated();

        // اگر slug اومده ولی خالی بود، دوباره بساز
        if (array_key_exists('slug', $data) && empty($data['slug'])) {
            $data['slug'] = str($data['name'] ?? $mockup->name)->slug('-') . '-' . str()->random(4);
        }

        $mockup->fill($data);

        if ($mockup->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * حذف موکاپ
     *
     * @group Mockup
     * @throws AuthorizationException
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Mockup::class);

        $mockup = Mockup::findOrFail($id);

        if ($mockup->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
