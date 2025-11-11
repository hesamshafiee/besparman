<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class ProductController extends Controller
{
    /**
     * لیست محصولات کاربر (کلاینت)
     * @group Product(Client)
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id        = (int) $request->query('id', 0);
        $order     = $request->query('order', 'id');
        $typeOrder = strtolower($request->query('type_order', 'desc'));
        $perPage   = (int) $request->query('per_page', 10);

        $allowed = ['id', 'created_at', 'updated_at', 'price', 'status', 'sort'];
        if (!in_array($order, $allowed, true)) $order = 'id';
        if (!in_array($typeOrder, ['asc', 'desc'], true)) $typeOrder = 'desc';

        $base = Product::query()->where('user_id', Auth::id());

        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new ProductResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(ProductResource::collection($paginator));
    }

    /**
     * ساخت محصول (کلاینت) + آپلود تصویر + تنظیمات
     * @group Product(Client)
     */
    public function clientStore(ProductRequest $request): JsonResponse
    {
        $data = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);

        $product = new Product($data);
        $product->user_id = Auth::id();
        if (empty($product->slug)) {
            $product->slug = Str::slug($product->name) . '-' . Str::random(4);
        }

        // اگر تصویر آمد، بعد از save مسیرها را ست می‌کنیم
        if (!$product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }

        if ($request->hasFile('image')) {
            [$orig, $prev] = $this->storeImageAndPreview($request->file('image'), $product->user_id);
            $product->original_path = $orig;
            $product->preview_path  = $prev;
            $product->save();
        }

        return response()->ok(__('general.savedSuccessfully'));
    }

    /**
     * بروزرسانی محصول (کلاینت)
     * @group Product(Client)
     */
    public function clientUpdate(ProductRequest $request, Product $product): JsonResponse
    {
        if ($product->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        $data = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);

        if (array_key_exists('slug', $data) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? $product->name) . '-' . Str::random(4);
        }

        $product->fill($data);

        // تصویر جدید؟
        if ($request->hasFile('image')) {
            // حذف قبلی‌ها
            $this->deleteImageIfExists($product->original_path);
            $this->deleteImageIfExists($product->preview_path);

            // ذخیره جدید
            [$orig, $prev] = $this->storeImageAndPreview($request->file('image'), $product->user_id);
            $product->original_path = $orig;
            $product->preview_path  = $prev;
        }

        if (!$product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.updatedSuccessfully', ['id' => $product->id]));
    }

    /**
     * حذف محصول (کلاینت) + پاکسازی فایل‌ها
     * @group Product(Client)
     */
    public function clientDestroy(Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        $orig = $product->original_path;
        $prev = $product->preview_path;

        if ($product->delete()) {
            $this->deleteImageIfExists($orig);
            $this->deleteImageIfExists($prev);
            return response()->ok(__('general.deletedSuccessfully', ['id' => $product->id]));
        }
        return response()->serverError(__('general.somethingWrong'));
    }


    public function clientBulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_id'                 => ['required', 'integer', 'exists:works,id'],

            // مقادیر عمومی (در صورت نبودن override در دسته)
            'name'                    => ['required', 'string', 'max:200'],
            'price'                   => ['required', 'integer', 'min:0'],
            'status'                  => ['nullable', 'integer', 'in:0,1'],
            'sku'                     => ['nullable', 'string', 'max:100'],
            'mockup_id'               => ['nullable', 'integer', 'exists:mockups,id'],
            'settings'                => ['nullable'], // json/array/string
            'options'                 => ['nullable'],
            'meta'                    => ['nullable'],

            // تصویر عمومی (اگر برای هر دسته تصویر ندادی)
            'image'                   => ['nullable', 'file', 'image', 'max:5120'],

            // لیست دسته‌ها (هر آیتم = یک محصول)
            'categories'                          => ['required', 'array', 'min:1'],
            'categories.*.category_id'            => ['required', 'integer', 'exists:categories,id'],
            'categories.*.address'                => ['nullable', 'string', 'max:100'],

            // overrideهای اختیاری مخصوص همان دسته
            'categories.*.name'                   => ['nullable', 'string', 'max:200'],
            'categories.*.price'                  => ['nullable', 'integer', 'min:0'],
            'categories.*.status'                 => ['nullable', 'integer', 'in:0,1'],
            'categories.*.sku'                    => ['nullable', 'string', 'max:100'],
            'categories.*.mockup_id'              => ['nullable', 'integer', 'exists:mockups,id'],
            'categories.*.settings'               => ['nullable'], // json/array/string
            'categories.*.options'                => ['nullable'],
            'categories.*.meta'                   => ['nullable'],

            // تصویر اختصاصی همان دسته (اختیاری)
            'categories.*.image'                  => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        // اطمینان از داشتن تصویر: یا عمومی، یا برای تک‌تک دسته‌ها
        $hasGlobal = $request->hasFile('image');
        if (! $hasGlobal) {
            foreach ($request->input('categories', []) as $idx => $row) {
                if (! $request->hasFile("categories.$idx.image")) {
                    return response()->json([
                        'message' => 'برای هر دسته تصویر لازم است؛ یا یک تصویر عمومی بده یا برای هر دسته تصویر جدا ارسال کن.',
                    ], 422);
                }
            }
        }

        // JSONهای عمومی را یکدست کنیم
        $global = $this->normalizeJsonFields($validated, ['settings', 'options', 'meta']);
        $userId = \Auth::id();
        $createdIds = [];

        \DB::beginTransaction();
        try {
            // اگر تصویر عمومی داریم، یک بار ذخیره کن
            $globalOrig = $globalPrev = null;
            if ($hasGlobal) {
                [$globalOrig, $globalPrev] = $this->storeImageAndPreview($request->file('image'), $userId);
            }

            foreach ($validated['categories'] as $idx => $cat) {
                // تصویر دسته (اگر هست) وگرنه عمومی
                if ($request->hasFile("categories.$idx.image")) {
                    [$orig, $prev] = $this->storeImageAndPreview($request->file("categories.$idx.image"), $userId);
                } else {
                    $orig = $globalOrig;
                    $prev = $globalPrev;
                }

                // settings/options/meta مخصوص دسته یا عمومی
                $catNormalized = $this->normalizeJsonFields($cat, ['settings', 'options', 'meta']);

                $p = new \App\Models\Product();
                $p->user_id          = $userId;
                $p->work_id          = (int) $validated['work_id'];
                $p->category_id      = (int) $cat['category_id'];

                // overrideهای دسته یا عمومی
                $name   = $cat['name']   ?? $validated['name'];
                $price  = $cat['price']  ?? $validated['price'];
                $status = array_key_exists('status', $cat)
                    ? (int) $cat['status']
                    : (isset($validated['status']) ? (int) $validated['status'] : 0);

                $p->name  = $name;
                $p->slug  = \Illuminate\Support\Str::slug($name) . '-' . \Illuminate\Support\Str::random(4);
                $p->price = (int) $price;
                $p->status = $status;

                // sku: اگر برای دسته آمد، همان؛ وگرنه عمومی. برای یکتا شدن کمی تصادفی.
                if (!empty($cat['sku'])) {
                    $p->sku = $cat['sku'] . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(4));
                } elseif (!empty($validated['sku'])) {
                    $p->sku = $validated['sku'] . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(4));
                }

                // mockup_id: مخصوص دسته > عمومی
                if (!empty($cat['mockup_id'])) {
                    $p->mockup_id = (int) $cat['mockup_id'];
                } elseif (!empty($validated['mockup_id'])) {
                    $p->mockup_id = (int) $validated['mockup_id'];
                }

                // settings/options/meta: مخصوص دسته > عمومی
                $p->settings = $catNormalized['settings'] ?? ($global['settings'] ?? null);
                $p->options  = $catNormalized['options']  ?? ($global['options']  ?? null);
                $p->meta     = $catNormalized['meta']     ?? ($global['meta']     ?? null);

                // مسیرها
                $p->original_path = $orig;
                $p->preview_path  = $prev;

                $p->save();
                $createdIds[] = $p->id;
            }

            \DB::commit();

            return response()->json([
                'status'  => true,
                'message' => __('general.savedSuccessfully'),
                'data'    => [
                    'work_id' => (int) $validated['work_id'],
                    'count'   => count($createdIds),
                    'ids'     => $createdIds,
                ],
            ]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            report($e);
            return response()->serverError(__('general.somethingWrong'));
        }
    }


    // ---------------------------------------------------------------------
    //                                 Admin
    // ---------------------------------------------------------------------

    /**
     * لیست محصولات (ادمین)
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Product::class);

        $id        = (int) $request->query('id', 0);
        $order     = $request->query('order', 'id');
        $typeOrder = strtolower($request->query('type_order', 'desc'));
        $perPage   = (int) $request->query('per_page', 10);
        $onlyTrashed = (bool) $request->boolean('only_trashed', false);
        $withTrashed = (bool) $request->boolean('with_trashed', false);

        $allowed = ['id', 'created_at', 'updated_at', 'price', 'status', 'sort'];
        if (!in_array($order, $allowed, true)) $order = 'id';
        if (!in_array($typeOrder, ['asc', 'desc'], true)) $typeOrder = 'desc';

        $base = Product::query();
        if ($onlyTrashed) $base->onlyTrashed();
        elseif ($withTrashed) $base->withTrashed();

        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new ProductResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(ProductResource::collection($paginator));
    }

    /**
     * ساخت محصول (ادمین)
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $data = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);
        $product = new Product($data);

        if (empty($product->slug)) {
            $product->slug = Str::slug($product->name) . '-' . Str::random(4);
        }

        if (!$product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }

        if ($request->hasFile('image')) {
            [$orig, $prev] = $this->storeImageAndPreview($request->file('image'), $product->user_id ?? Auth::id());
            $product->original_path = $orig;
            $product->preview_path  = $prev;
            $product->save();
        }

        return response()->ok(__('general.savedSuccessfully'));
    }

    /**
     * بروزرسانی محصول (ادمین)
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Product::class);

        $product = Product::findOrFail($id);
        $data    = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);

        if (array_key_exists('slug', $data) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? $product->name) . '-' . Str::random(4);
        }

        $product->fill($data);

        if ($request->hasFile('image')) {
            $this->deleteImageIfExists($product->original_path);
            $this->deleteImageIfExists($product->preview_path);

            [$orig, $prev] = $this->storeImageAndPreview($request->file('image'), $product->user_id ?? Auth::id());
            $product->original_path = $orig;
            $product->preview_path  = $prev;
        }

        if (!$product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.updatedSuccessfully', ['id' => $id]));
    }

    /**
     * حذف محصول (ادمین)
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', Product::class);

        $orig = $product->original_path;
        $prev = $product->preview_path;

        if ($product->delete()) {
            $this->deleteImageIfExists($orig);
            $this->deleteImageIfExists($prev);
            return response()->ok(__('general.deletedSuccessfully', ['id' => $product->id]));
        }
        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * بازیابی محصول حذف‌شده (ادمین)
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function restore(int $id)
    {
        $this->authorize('create', Product::class);

        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return response()->ok(__('general.restoredSuccessfully'));
    }



    // ===========================
    // Helpers (تصویر و JSON)
    // ===========================

    /**
     * نرمال‌سازی فیلدهای JSON: هم String JSON و هم Array را قبول می‌کند.
     */
    private function normalizeJsonFields(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) continue;
            if (is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                }
            }
        }
        return $data;
    }

    /**
     * ذخیره تصویر و ساخت پیش‌نمایش (public disk).
     * اگر کتابخانه‌ی تصویر نداری، preview همان کپی فایل اصلی خواهد بود.
     *
     * @return array [$originalPath, $previewPath]
     */
    private function storeImageAndPreview($uploadedFile, int $userId): array
    {
        $disk = 'public';
        $baseDir = "products/{$userId}";
        $ext  = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');
        $uuid = (string) Str::uuid();

        $originalRel = "{$baseDir}/{$uuid}.{$ext}";
        $previewRel  = "{$baseDir}/{$uuid}_preview.{$ext}";

        // ذخیره فایل اصلی
        Storage::disk($disk)->put($originalRel, file_get_contents($uploadedFile->getRealPath()));

        // ساخت پیش‌نمایش
        $this->makePreview($disk, $originalRel, $previewRel);

        // مسیرهای وب (storage لینک سمبولیک داشته باشی)
        return [
            "/storage/{$originalRel}",
            "/storage/{$previewRel}",
        ];
    }

    /**
     * ساخت پیش‌نمایش (در صورت نبود Intervention، فقط کپی می‌کند)
     */
    private function makePreview(string $disk, string $originalRel, string $previewRel): void
    {
        // اگر Intervention/Image داری، اینجا ری‌سایز واقعی انجام بده:
        // try {
        //     $img = \Intervention\Image\Facades\Image::make(Storage::disk($disk)->path($originalRel));
        //     $img->resize(800, null, function ($c) { $c->aspectRatio(); $c->upsize(); });
        //     $img->save(Storage::disk($disk)->path($previewRel), 85, 'jpg');
        // } catch (\Throwable $e) {
        //     Storage::disk($disk)->copy($originalRel, $previewRel);
        // }

        // حالت ساده (بدون وابستگی)
        Storage::disk($disk)->copy($originalRel, $previewRel);
    }

    private function deleteImageIfExists(?string $publicPath): void
    {
        if (!$publicPath) return;
        // publicPath مثل /storage/products/uid/file.jpg است => باید به مسیر روی دیسک تبدیل شود
        $relative = ltrim(str_replace('/storage/', '', $publicPath), '/');
        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
