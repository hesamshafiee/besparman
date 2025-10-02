<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CategoryRequest;
use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\V1\Image\Image;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Category
     */
    public function index(Request $request) : JsonResponse
    {
        $this->authorize('show', Category::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new CategoryResource(Category::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(CategoryResource::collection(Category::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param CategoryRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Category
     */
    public function store(CategoryRequest $request) : JsonResponse
    {

        $this->authorize('create', Category::class);

        $category = new Category();

        $data = $request->safe()->all();

        if (isset($data['data']) && is_string($data['data'])) {
            $data['data'] = json_decode($data['data'], true);
        }

        $category->fill($data);


        if ($category->save()) {
            if ($request->exists('images')) {
                Image::modelImages($category, $request->file('images'), Image::DRIVER_PUBLIC);
            }
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));

    }


    /**
     * @param CategoryRequest $request
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Category
     */
    public function update(CategoryRequest $request, int $id) : JsonResponse
    {
        $this->authorize('update', Category::class);

        $category = Category::findOrFail($id);

        $data = $request->safe()->all();

        if (isset($data['data']) && is_string($data['data'])) {
            $data['data'] = json_decode($data['data'], true);
        }

        $category->fill($data);

        if ($category->save()) {
            $categoryName = '';
            $groupedProducts = $category->products()
                ->withPivot('address')
                ->get()
                ->groupBy('pivot.address')
                ->map(function ($products) {
                    return $products->pluck('id')->toArray();
                })
                ->toArray();

            $data = is_string($category->data) ? json_decode($category->data, true) : $category->data;

            foreach ($groupedProducts as $address => $ids) {
                foreach ($data as $item) {
                    if ($item['id'] == $address) {
                        $path = [];
                        $current = $item;

                        $path[] = $current['text'];

                        while ($current['parent'] != 0) {
                            foreach ($data as $parentItem) {
                                if ($parentItem['id'] == $current['parent']) {
                                    $path[] = $parentItem['text'];
                                    $current = $parentItem;
                                    break;
                                }
                            }
                        }

                        $path = array_reverse($path);

                        $categoryName .= implode(' > ', $path);

                        Product::whereIn('id', $ids)
                            ->chunkById(100, function ($products) use ($categoryName) {
                                foreach ($products as $product) {
                                    $product->update([
                                        'category_name' => $categoryName,
                                    ]);
                                }
                            });

                        $categoryName = '';
                    }
                }
            }
            if ($request->exists('images')) {
                Image::modelImages($category, $request->file('images'), Image::DRIVER_PUBLIC);
            }
            return response()->ok(__('general.updatedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));

    }

    /**
     * @param int $id
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @group Category
     */
    public function destroy(int $id) : JsonResponse
    {
        $this->authorize('delete', Category::class);

        $category = Category::findOrFail($id);

        if ($category->delete()) {
            Image::deletingModelImages($category, Image::DRIVER_PUBLIC);
            return response()->ok(__('general.deletedSuccessfully', ['id' => $id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
