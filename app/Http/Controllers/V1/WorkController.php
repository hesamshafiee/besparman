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
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * لیست محصولات کاربر (کلاینت)
     *
     * @group Product(Client)
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id        = (int) $request->query('id', 0);
        $order     = $request->query('order', 'id');
        $typeOrder = strtolower($request->query('type_order', 'desc'));
        $perPage   = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at', 'price', 'status', 'sort'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }
        if (!in_array($typeOrder, ['asc','desc'], true)) {
            $typeOrder = 'desc';
        }

        $base = Product::query()->where('user_id', Auth::id());

        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new ProductResource($item));
        }

        // فیلترهای سمت کلاینت (اختیاری)
        if ($request->filled('category_id')) {
            $base->where('category_id', (int) $request->query('category_id'));
        }
        if ($request->filled('status')) {
            $base->where('status', (int) $request->query('status'));
        }
        if ($q = $request->query('q')) {
            $base->where(function ($x) use ($q) {
                $x->where('name', 'like', "%{$q}%")
                  ->orWhere('slug', 'like', "%{$q}%")
                  ->orWhere('sku',  'like', "%{$q}%");
            });
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(ProductResource::collection($paginator));
    }

    /**
     * ساخت محصول (کلاینت)
     *
     * @group Product(Client)
     */
    public function clientStore(ProductRequest $request): JsonResponse
    {
        // مالکیت: user_id از prepareForValidation روی کاربر لاگین پر می‌شود
        $data    = $request->validated();
        $product = new Product($data);

        if (empty($product->slug)) {
            $product->slug = Str::slug($product->name).'-'.Str::random(4);
        }
        $product->user_id = Auth::id();

        if (! $product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.savedSuccessfully'));
    }

    /**
     * بروزرسانی محصول (کلاینت)
     *
     * @group Product(Client)
     */
    public function clientUpdate(ProductRequest $request, Product $product): JsonResponse
    {
        if ($product->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        $data = $request->validated();

        // اگر slug کلید داده شده ولی خالی بود، دوباره بسازیم
        if (array_key_exists('slug', $data) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? $product->name).'-'.Str::random(4);
        }

        $product->fill($data);

        if (! $product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.updatedSuccessfully', ['id' => $product->id]));
    }

    /**
     * حذف محصول (کلاینت)
     *
     * @group Product(Client)
     */
    public function clientDestroy(Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        if ($product->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $product->id]));
        }
        return response()->serverError(__('general.somethingWrong'));
    }

    // ---------------------------------------------------------------------
    //                                 Admin
    // ---------------------------------------------------------------------

    /**
     * لیست محصولات (ادمین) با سافت‌دیلیت و فیلترها
     *
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

        $allowedColumns = ['id', 'created_at', 'updated_at', 'price', 'status', 'sort'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }
        if (!in_array($typeOrder, ['asc','desc'], true)) {
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

        // فیلترهای ادمین
        if ($request->filled('user_id')) {
            $base->where('user_id', (int) $request->query('user_id'));
        }
        if ($request->filled('category_id')) {
            $base->where('category_id', (int) $request->query('category_id'));
        }
        if ($request->filled('status')) {
            $base->where('status', (int) $request->query('status'));
        }
        if ($request->filled('min_price')) {
            $base->where('price', '>=', (int) $request->query('min_price'));
        }
        if ($request->filled('max_price')) {
            $base->where('price', '<=', (int) $request->query('max_price'));
        }
        if ($q = $request->query('q')) {
            $base->where(function ($x) use ($q) {
                $x->where('name', 'like', "%{$q}%")
                  ->orWhere('slug', 'like', "%{$q}%")
                  ->orWhere('sku',  'like', "%{$q}%");
            });
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(ProductResource::collection($paginator));
    }

    /**
     * ساخت محصول (ادمین)
     *
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $data    = $request->validated();
        $product = new Product($data);

        if (empty($product->slug)) {
            $product->slug = Str::slug($product->name).'-'.Str::random(4);
        }

        if (! $product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.savedSuccessfully'));
    }

    /**
     * بروزرسانی محصول (ادمین)
     *
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Product::class);

        $product = Product::findOrFail($id);
        $data    = $request->validated();

        if (array_key_exists('slug', $data) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? $product->name).'-'.Str::random(4);
        }

        $product->fill($data);

        if (! $product->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.updatedSuccessfully', ['id' => $id]));
    }

    /**
     * حذف محصول (ادمین)
     *
     * @group Product(Admin)
     * @throws AuthorizationException
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', Product::class);

        if ($product->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $product->id]));
        }
        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * بازیابی محصول حذف‌شده (ادمین)
     *
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
}
