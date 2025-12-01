<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\OptionRequest;
use App\Http\Resources\V1\OptionResource;
use App\Models\Option;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    /**
     * لیست Optionها (پنل ادمین)
     *

     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Option
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Option::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(
                new OptionResource(Option::findOrFail($id))
            );
        }

        $order    = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage  = (int) $request->query('per_page', 10);
        $onlyActive = (int) $request->query('only_active', 0);

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $query = Option::query();

        if ($onlyActive) {
            $query->where('is_active', 1);
        }

        // اگر خواستی محدودیت روی ستون‌های مجاز بزاری:
        // $allowedColumns = ['id','created_at','updated_at','sort_order','name','code'];
        // if (!in_array($order, $allowedColumns)) { $order = 'id'; }

        $query->orderBy($order, $typeOrder);

        return response()->jsonMacro(
            OptionResource::collection($query->paginate($perPage))
        );
    }

    /**
     * لیست Optionها برای کلاینت (مثلاً Designer / Buyer)
     *
     * فقط Optionهای فعال
     *
     * @param Request $request
     * @return JsonResponse
     * @group Option
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(
                new OptionResource(
                    Option::where('is_active', 1)->where('id', $id)->firstOrFail()
                )
            );
        }

        $order    = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage  = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at', 'sort_order', 'name'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $query = Option::where('is_active', 1)->orderBy($order, $typeOrder);

        return response()->jsonMacro(
            OptionResource::collection($query->paginate($perPage))
        );
    }

    /**
     * ساخت Option جدید
     *
     * @param OptionRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Option
     */
    public function store(OptionRequest $request): JsonResponse
    {
        $this->authorize('create', Option::class);

        $option = new Option();
        $option->fill($request->safe()->all());

        if ($option->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * بروزرسانی Option
     *
     * @param OptionRequest $request
     * @param Option $option
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Option
     */
    public function update(OptionRequest $request, Option $option): JsonResponse
    {
        $this->authorize('update', Option::class);

        $option->fill($request->safe()->all());

        if ($option->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $option->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * حذف Option
     *
     * @param Option $option
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Option
     */
    public function destroy(Option $option): JsonResponse
    {
        $this->authorize('delete', Option::class);

        if ($option->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $option->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
