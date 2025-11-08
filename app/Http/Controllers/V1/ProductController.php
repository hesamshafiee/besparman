<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProductRequest;
use App\Http\Resources\V1\ClientProductWithCategoryResource;
use App\Http\Resources\V1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\V1\Image\Image;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Product
     */
    public function index(Request $request) : JsonResponse
    {
        $this->authorize('show', Product::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro( new ProductResource(Product::where('id', $id)->where('private', 0)->with('categories')->firstOrFail()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        return response()->jsonMacro(ProductResource::collection(Product::where('private', 0)->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Product
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro( new ClientProductWithCategoryResource(Product::where('id', $id)->where('status', 1)->where('private', 0)->firstOrFail()));
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

        return response()->jsonMacro(ClientProductWithCategoryResource::collection(Product::where('status', 1)->where('private', 0)->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Product
     */
    public function privateIndex(Request $request) : JsonResponse
    {
        $this->authorize('private', Product::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro( new ProductResource(Product::where('id', $id)->where('private', 1)->with('categories')->firstOrFail()));
        }

        return response()->jsonMacro(ProductResource::collection(Product::where('private', 1)->orderByDesc('created_at')->with('categories')->paginate(100)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Product
     */
    public function privateClientIndex(Request $request): JsonResponse
    {
        if (Auth::user()->private) {
            $id = (int) $request->query('id', 0);
            if ($id) {
                return response()->jsonMacro( new ClientProductWithCategoryResource(Product::where('id', $id)->where('status', 1)->where('private', 1)->firstOrFail()));
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

            return response()->jsonMacro(ClientProductWithCategoryResource::collection(Product::where('status', 1)->where('private', 1)->orderBy($order, $typeOrder)->paginate($perPage)));
        }

        return response()->serverError('Access Denied');
    }

    /**
     * @param ProductRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Product
     */
    public function store(ProductRequest $request) : JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = new Product();
        $product->fill($request->safe()->all());

        if ($product->save()) {
            if ($request->exists('images')) {
                Image::modelImages($product, $request->file('images'), Image::DRIVER_PUBLIC);
            }
            return response()->ok('Saved successfully id: ' . $product->id);
        }

        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     * @param ProductRequest $request
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Product
     */
    public function update(ProductRequest $request, int $id) : JsonResponse
    {
        $this->authorize('update', Product::class);

        $product = Product::findOrFail($id);
        $product->fill($request->safe()->all());

        if ($product->save()) {
            if ($request->exists('images')) {
                Image::modelImages($product, $request->file('images'), Image::DRIVER_PUBLIC);
            }
            return response()->ok(__('general.updatedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));

    }


        /**
     * @param ProductRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Product
     */
    public function bulkUpdate(ProductRequest $request): JsonResponse
    {
        $this->authorize('update', Product::class);

        $products = $request->input('products');

        if (empty($products)) {
            return response()->serverError('Product list cannot be empty.');
        }

        $updateColumns = array_keys(reset($products));
        $updateColumns = array_diff($updateColumns, ['id']);

        Product::upsert($products, ['id'], $updateColumns);

        return response()->ok(__('general.updatedSuccessfully'));
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Product
     */
    public function destroy(int $id) : JsonResponse
    {
        $this->authorize('delete', Product::class);

        $product = Product::findOrFail($id);

        if ($product->delete()) {
            Image::deletingModelImages($product, Image::DRIVER_PUBLIC);
            return response()->ok(__('general.deletedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    public function options(Product $product, ProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $options = $product->options;

        $options[$request->option] = [];
        foreach ($request->safe() as $key => $value) {
            $options[$request->option][$key] = $value;
        }

        $product->options = $options;

        if ($product->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     *
     * @param Product $product
     * @param ProductRequest $request
     * @return JsonResponse
     * @group Product
     */
    public function assignCategoryToProduct(Product $product, ProductRequest $request): JsonResponse
    {
        $this->authorize('update', Product::class);

        $syncData = [];

        if ($request->has('category_id') && $request->has('address')) {
            $syncData = [
                $request->category_id => $request->only('address') ?? '{}'
            ];

            $category = Category::find($request->category_id);
            $data = is_string($category->data) ? json_decode($category->data, true) : $category->data;
            $categoryName = '';

            foreach ($data as $item) {
                if ($item['id'] == $request->only('address')['address']) {
                    $categoryName .= $item['text'];
                    $parent = $item['parent'];
                    while ($parent !== 0) {
                        foreach ($category->data as $value) {
                            if ($value['id'] == $parent) {
                                $categoryName .= ' > ' . $value['text'];
                                $parent = $value['parent'];
                            }
                        }
                    }
                }
            }

            $product->category_name = $categoryName;
            $product->save();
        }

        $product->categories()->sync($syncData);

        return response()->ok('Category has been assigned to product');

    }
}
