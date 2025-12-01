<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CategoryOptionRequest;
use App\Http\Resources\V1\CategoryOptionResource;
use App\Models\CategoryOption;
use App\Models\Category;
use App\Models\Option;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryOptionController extends Controller
{
    /**
     * لیست Optionهای یک Category (ادمین)
     *
     * @param Request $request
     * @param Category $category
     * @return JsonResponse
     * @throws AuthorizationException
     * @group CategoryOption
     */
    public function index(Request $request, Category $category): JsonResponse
    {
        $this->authorize('show', CategoryOption::class);

        $order     = $request->query('order', 'category_option.sort_order');
        $typeOrder = $request->query('type_order', 'asc');

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'asc';
        }

        $query = $category->options()
            ->withPivot(['is_required', 'is_active', 'sort_order'])
            ->orderBy($order, $typeOrder);

        return response()->jsonMacro(
            CategoryOptionResource::collection($query->get())
        );
    }

    /**
     * لیست Optionهای فعال یک Category برای کلاینت
     *
     * فقط Optionهایی که:
     * - خود Option is_active = 1
     * - و در pivot is_active = 1
     *
     * @param Request $request
     * @param Category $category
     * @return JsonResponse
     * @group CategoryOption
     */
    public function clientIndex(Request $request, Category $category): JsonResponse
    {
        $order     = $request->query('order', 'category_option.sort_order');
        $typeOrder = $request->query('type_order', 'asc');

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'asc';
        }

        $query = $category->options()
            ->where('options.is_active', 1)
            ->wherePivot('is_active', 1)
            ->withPivot(['is_required', 'is_active', 'sort_order'])
            ->orderBy($order, $typeOrder);

        return response()->jsonMacro(
            CategoryOptionResource::collection($query->get())
        );
    }

    /**
     * همگام‌سازی Optionهای یک Category
     *
     * ساختار ورودی:
     * {
     *   "options": [
     *     { "option_id": 1, "is_required": true, "is_active": true, "sort_order": 0 },
     *     ...
     *   ]
     * }
     *
     * @param CategoryOptionRequest $request
     * @param Category $category
     * @return JsonResponse
     * @throws AuthorizationException
     * @group CategoryOption
     */
    public function sync(CategoryOptionRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', CategoryOption::class);

        $payload = collect($request->validated('options'))
            ->mapWithKeys(function ($row) {
                return [
                    (int) $row['option_id'] => [
                        'is_required' => $row['is_required'] ?? null,
                        'is_active'   => $row['is_active'] ?? true,
                        'sort_order'  => $row['sort_order'] ?? 0,
                    ],
                ];
            })
            ->all();

        $ids = array_keys($payload);
        $foundIds = Option::whereIn('id', $ids)->pluck('id')->all();
        $missing  = array_values(array_diff($ids, $foundIds));

        if (!empty($missing)) {
            return response()->serverError(__('general.somethingWrong')); // یا پیام خاص
        }

        $category->options()->sync($payload);

        return response()->ok(__('general.updatedSuccessfully', ['id' => $category->id]));
    }

    /**
     * جدا کردن یک Option از Category
     *
     * @param Category $category
     * @param Option $option
     * @return JsonResponse
     * @throws AuthorizationException
     * @group CategoryOption
     */
    public function destroy(Category $category, Option $option): JsonResponse
    {
        $this->authorize('delete', CategoryOption::class);

        $category->options()->detach($option->id);

        return response()->ok(__('general.deletedSuccessfully', ['id' => $option->id]));
    }
}
