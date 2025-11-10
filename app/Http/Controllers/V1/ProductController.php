<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * @group Product
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Product::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new ProductResource(Product::findOrFail($id)));
        }

        $order    = $request->query('order', 'id');
        $type     = strtolower($request->query('type_order', 'desc'));
        $perPage  = (int) $request->query('per_page', 10);
        $type     = in_array($type, ['asc','desc']) ? $type : 'desc';

        $q = Product::query();

        if ($request->filled('category_id')) {
            $q->where('category_id', (int) $request->query('category_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', (int) $request->query('status'));
        }
        if ($search = $request->query('q')) {
            $q->where(function($x) use ($search) {
                $x->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%");
            });
        }

        return response()->jsonMacro(
            ProductResource::collection($q->orderBy($order, $type)->paginate($perPage))
        );
    }

    /**
     * @group Product
     * @throws AuthorizationException
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();
        $product = new Product($data);

        if (empty($product->slug)) {
            $product->slug = Str::slug($product->name).'-'.Str::random(4);
        }

        if ($product->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @group Product
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

        if ($product->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @group Product
     * @throws AuthorizationException
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Product::class);

        $product = Product::findOrFail($id);

        if ($product->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
