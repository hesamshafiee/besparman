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

class ProductController extends Controller
{
    /**
     * @group Product(Client)
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id        = (int) $request->query('id', 0);
        $order     = $request->query('order', 'id');
        $typeOrder = strtolower($request->query('type_order', 'desc'));
        $perPage   = (int) $request->query('per_page', 10);

        $allowed = ['id', 'created_at', 'updated_at', 'price', 'status', 'sort'];
        if (!in_array($order, $allowed, true)) {
            $order = 'id';
        }
        if (!in_array($typeOrder, ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $base = Product::query()->where('user_id', Auth::id());

        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new ProductResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(ProductResource::collection($paginator));
    }

    /**
     * @group Product(Client)
     */
    public function clientStore(ProductRequest $request): JsonResponse
    {
        $data = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);

        $product = new Product($data);
        $product->user_id = Auth::id();

        if (empty($product->slug)) {
            $product->slug = Str::slug($product->name ?? 'product') . '-' . Str::random(4);
        }

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
     * @group Product(Client)
     */
    public function clientUpdate(ProductRequest $request, Product $product): JsonResponse
    {
        if ($product->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        $data = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);

        if (array_key_exists('slug', $data) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? $product->name ?? 'product') . '-' . Str::random(4);
        }

        $product->fill($data);

        if ($request->hasFile('image')) {
            $this->deleteImageIfExists($product->original_path);
            $this->deleteImageIfExists($product->preview_path);

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

    /**
     * ساخت انبوه محصول برای چند variant (کلاینت)
     * قبلاً روی category_id بود، الان روی variant_id
     *
     * @group Product(Client)
     */
    public function clientBulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_id'                 => ['required', 'integer', 'exists:works,id'],

            'name'                    => ['required', 'string', 'max:200'],
            'price'                   => ['required', 'integer', 'min:0'],
            'status'                  => ['nullable', 'integer', 'in:0,1'],
            'sku'                     => ['nullable', 'string', 'max:100'],
            'mockup_id'               => ['nullable', 'integer', 'exists:mockups,id'],
            'settings'                => ['nullable'], 
            'options'                 => ['nullable'],
            'meta'                    => ['nullable'],

            'image'                   => ['nullable', 'file', 'image', 'max:5120'],

            'variants'                           => ['required', 'array', 'min:1'],
            'variants.*.variant_id'              => ['required', 'integer', 'exists:variants,id'],
            'variants.*.address'                 => ['nullable', 'string', 'max:100'], // اگر در meta استفاده می‌کنی

            'variants.*.name'                    => ['nullable', 'string', 'max:200'],
            'variants.*.price'                   => ['nullable', 'integer', 'min:0'],
            'variants.*.status'                  => ['nullable', 'integer', 'in:0,1'],
            'variants.*.sku'                     => ['nullable', 'string', 'max:100'],
            'variants.*.mockup_id'               => ['nullable', 'integer', 'exists:mockups,id'],
            'variants.*.settings'                => ['nullable'], // json/array/string
            'variants.*.options'                 => ['nullable'],
            'variants.*.meta'                    => ['nullable'],

            'variants.*.image'                   => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $hasGlobal = $request->hasFile('image');
        if (!$hasGlobal) {
            foreach ($request->input('variants', []) as $idx => $row) {
                if (!$request->hasFile("variants.$idx.image")) {
                    return response()->json([
                        'message' => 'برای هر واریانت تصویر لازم است؛ یا یک تصویر عمومی بده یا برای هر واریانت تصویر جدا ارسال کن.',
                    ], 422);
                }
            }
        }

        $global = $this->normalizeJsonFields($validated, ['settings', 'options', 'meta']);
        $userId = Auth::id();
        $createdIds = [];

        \DB::beginTransaction();
        try {
            $globalOrig = $globalPrev = null;
            if ($hasGlobal) {
                [$globalOrig, $globalPrev] = $this->storeImageAndPreview($request->file('image'), $userId);
            }

            foreach ($validated['variants'] as $idx => $row) {

                if ($request->hasFile("variants.$idx.image")) {
                    [$orig, $prev] = $this->storeImageAndPreview($request->file("variants.$idx.image"), $userId);
                } else {
                    $orig = $globalOrig;
                    $prev = $globalPrev;
                }

                $rowNormalized = $this->normalizeJsonFields($row, ['settings', 'options', 'meta']);

                $p = new Product();
                $p->user_id = $userId;
                $p->work_id = (int) $validated['work_id'];
                $p->variant_id = (int) $row['variant_id'];

                // overrideهای هر واریانت یا عمومی
                $name = $row['name'] ?? $validated['name'];
                $price = $row['price'] ?? $validated['price'];
                $status = array_key_exists('status', $row)
                    ? (int) $row['status']
                    : (isset($validated['status']) ? (int) $validated['status'] : 0);

                $p->name = $name;
                $p->slug = Str::slug($name) . '-' . Str::random(4);
                $p->price = (int) $price;
                $p->status = $status;

                // sku: اگر برای آیتم آمد، همان؛ وگرنه عمومی. برای یکتا شدن کمی تصادفی.
                if (!empty($row['sku'])) {
                    $p->sku = $row['sku'] . '-' . Str::lower(Str::random(4));
                } elseif (!empty($validated['sku'])) {
                    $p->sku = $validated['sku'] . '-' . Str::lower(Str::random(4));
                }

                // mockup_id: مخصوص آیتم > عمومی
                if (!empty($row['mockup_id'])) {
                    $p->mockup_id = (int) $row['mockup_id'];
                } elseif (!empty($validated['mockup_id'])) {
                    $p->mockup_id = (int) $validated['mockup_id'];
                }

                // settings/options/meta: مخصوص آیتم > عمومی
                $p->settings = $rowNormalized['settings'] ?? ($global['settings'] ?? null);
                $p->options  = $rowNormalized['options']  ?? ($global['options']  ?? null);
                $p->meta     = $rowNormalized['meta']     ?? ($global['meta']     ?? null);

                // اگر address را می‌خواهی در meta ذخیره کنی:
                if (!empty($row['address'])) {
                    $meta = $p->meta ?? [];
                    $meta['address'] = $row['address'];
                    $p->meta = $meta;
                }

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
             dd([
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        // 'trace'   => $e->getTraceAsString(), // اگر جزییات بیشتر خواستی، این رو هم باز کن
    ]);
            \DB::rollBack();
            report($e);
            return response()->serverError(__('general.somethingWrong'));
        }
    }

    // ---------------------------------------------------------------------
    //                                 Admin
    // ---------------------------------------------------------------------

    /**
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Product::class);

        $id          = (int) $request->query('id', 0);
        $order       = $request->query('order', 'id');
        $typeOrder   = strtolower($request->query('type_order', 'desc'));
        $perPage     = (int) $request->query('per_page', 10);
        $onlyTrashed = (bool) $request->boolean('only_trashed', false);
        $withTrashed = (bool) $request->boolean('with_trashed', false);

        $allowed = ['id', 'created_at', 'updated_at', 'price', 'status', 'sort'];
        if (!in_array($order, $allowed, true)) {
            $order = 'id';
        }
        if (!in_array($typeOrder, ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $base = Product::query();
        if ($onlyTrashed) {
            $base->onlyTrashed();
        } elseif ($withTrashed) {
            $base->withTrashed();
        }

        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new ProductResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(ProductResource::collection($paginator));
    }

    /**
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $data = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);
        $product = new Product($data);

        if (empty($product->slug)) {
            $product->slug = Str::slug($product->name ?? 'product') . '-' . Str::random(4);
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
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Product::class);

        $product = Product::findOrFail($id);
        $data    = $this->normalizeJsonFields($request->validated(), ['settings', 'options', 'meta']);

        if (array_key_exists('slug', $data) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? $product->name ?? 'product') . '-' . Str::random(4);
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



    private function normalizeJsonFields(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
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

        Storage::disk($disk)->put($originalRel, file_get_contents($uploadedFile->getRealPath()));

        $this->makePreview($disk, $originalRel, $previewRel);

        return [
            "/storage/{$originalRel}",
            "/storage/{$previewRel}",
        ];
    }

    private function makePreview(string $disk, string $originalRel, string $previewRel): void
    {
        Storage::disk($disk)->copy($originalRel, $previewRel);
    }

    private function deleteImageIfExists(?string $publicPath): void
    {
        if (!$publicPath) {
            return;
        }

        $relative = ltrim(str_replace('/storage/', '', $publicPath), '/');
        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
