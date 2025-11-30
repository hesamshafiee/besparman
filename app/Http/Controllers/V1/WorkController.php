<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WorkResource;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

use function Symfony\Component\Clock\now;

class WorkController extends Controller
{
    /**
     * لیست کارهای کاربر جاری
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int)$request->query('id', 0);

        if ($id) {
            $work = Work::where([
                'user_id' => Auth::id(),
                'id'      => $id,
            ])->firstOrFail();

            return response()->jsonMacro(new WorkResource($work));
        }

        $order     = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage   = (int)$request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $items = Work::where('user_id', Auth::id())
            ->orderBy($order, $typeOrder)
            ->paginate($perPage);

        return response()->jsonMacro(WorkResource::collection($items));
    }

    /**
     * ایجاد work جدید
     */
    public function clientStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'image'         => ['nullable', 'image', 'max:4096'],
        ]);

        $work = new Work();
        $work->fill($request->only(['title', 'description']));
        $work->user_id      = Auth::id();
        $work->slug         = Work::makeSlug($validated['title']);
        $work->is_published = Work::IS_PUBLISHED_FALSE;
        //$work->published_at = $request->date('published_at') ?? now();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('works', config('filesystems.default'));
            $work->image = $path;
        }

        if (!$work->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }

        return response()->ok(__('general.savedSuccessfully'));
    }

    /**
     * بروزرسانی work
     */
    public function clientUpdate(Request $request, Work $work): JsonResponse
    {
        $validated = $request->validate([
            'title'         => ['sometimes', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'image'         => ['nullable', 'image', 'max:4096'],
        ]);

        if ($work->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        if (isset($validated['title'])) {
            $work->title = $validated['title'];
            $work->slug  = Work::makeSlug($validated['title']);
        }

        if (array_key_exists('description', $validated)) {
            $work->description = $validated['description'];
        }

        $work->is_published = Work::IS_PUBLISHED_FALSE;

        // آپلود عکس جدید
        if ($request->hasFile('image')) {

            // حذف عکس قبلی در صورت وجود
            if ($work->image && Storage::disk(config('filesystems.default'))->exists($work->image)) {
                Storage::disk(config('filesystems.default'))->delete($work->image);
            }

            $path = $request->file('image')->store('works', config('filesystems.default'));
            $work->image = $path;
        }

        if (!$work->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }

        return response()->ok(__('general.updatedSuccessfully'));
    }

    /**
     * حذف work توسط کاربر
     */
    public function clientDestroy(Work $work)
    {
        if ($work->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        // حذف عکس
        if ($work->image && Storage::disk(config('filesystems.default'))->exists($work->image)) {
            Storage::disk(config('filesystems.default'))->delete($work->image);
        }

        if ($work->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $work->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * ادمین — لیست کامل
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Work::class);

        $id        = (int)$request->query('id', 0);
        $order     = $request->query('order', 'id');
        $typeOrder = strtolower($request->query('type_order', 'desc'));
        $perPage   = (int)$request->query('per_page', 10);

        $onlyTrashed = (bool)$request->boolean('only_trashed', false);
        $withTrashed = (bool)$request->boolean('with_trashed', false);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }

        if (!in_array($typeOrder, ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $base = Work::query();

        if ($onlyTrashed) {
            $base->onlyTrashed();
        } elseif ($withTrashed) {
            $base->withTrashed();
        }

        if ($id) {
            $item = (clone $base)->findOrFail($id);
            return response()->jsonMacro(new WorkResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);

        return response()->jsonMacro(WorkResource::collection($paginator));
    }

    /**
     * ادمین — حذف
     */
    public function destroy(Work $work)
    {
        $this->authorize('show', Work::class);

        if ($work->image && Storage::disk(config('filesystems.default'))->exists($work->image)) {
            Storage::disk(config('filesystems.default'))->delete($work->image);
        }

        if ($work->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $work->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * بازگردانی (Admin)
     */
    public function restore(int $id)
    {
        $this->authorize('create', Work::class);

        $artwork = Work::onlyTrashed()->findOrFail($id);
        $artwork->restore();

        return response()->ok(__('general.restoredSuccessfully'));
    }

    /**
     * فقط ادمین حق دارد وضعیت انتشار را تغییر دهد
     * 0 = پیش‌نویس
     * 1 = منتشر شده
     * 2 = آرشیو شده
     */
    public function updatePublishStatus(Request $request, Work $work): JsonResponse
    {
        // چک مجوز — ادمین
        //$this->authorize('update', Work::class);

        // اعتبارسنجی
        $validated = $request->validate([
            'is_published' => ['required', 'integer', 'in:0,1,2'],
        ]);

        // ذخیره مقدار جدید
        $work->is_published = $validated['is_published'];
        $work->published_at = now();

        if (!$work->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }

        return response()->ok(__('work.publishStatusUpdatedSuccessfully'));
    }
}
