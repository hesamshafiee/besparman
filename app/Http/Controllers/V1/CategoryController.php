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


        if (isset($data['default_setting']) && is_string($data['default_setting'])) {
            $data['default_setting'] = json_decode($data['default_setting'], true);
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

        if (isset($data['default_setting']) && is_string($data['default_setting'])) {
            $data['default_setting'] = json_decode($data['default_setting'], true);
        }

        $category->fill($data);

        if ($category->save()) {
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
