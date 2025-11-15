<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\OptionValueRequest;
use App\Http\Resources\V1\OptionValueResource;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OptionValueController extends Controller
{
    /**
     * لیست OptionValueهای یک Option (ادمین)
     *
     * کوئری‌ها:
     * - id: اگر باشد، همان value خاص را برمی‌گرداند
     * - only_active: فقط فعال‌ها
     * - order, type_order, per_page
     *
     * @param Request $request
     * @param Option $option
     * @return JsonResponse
     * @throws AuthorizationException
     * @group OptionValue
     */
    public function index(Request $request, Option $option): JsonResponse
    {
        $this->authorize('show', OptionValue::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            $value = OptionValue::where('option_id', $option->id)->findOrFail($id);
            return response()->jsonMacro(new OptionValueResource($value));
        }

        $order      = $request->query('order', 'id');
        $typeOrder  = $request->query('type_order', 'desc');
        $perPage    = (int) $request->query('per_page', 20);
        $onlyActive = (int) $request->query('only_active', 0);

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $query = OptionValue::where('option_id', $option->id);

        if ($onlyActive) {
            $query->where('is_active', 1);
        }

        // می‌توانی محدودیت ستون‌ها را هم مثل پایین اعمال کنی:
        $allowedColumns = ['id', 'created_at', 'updated_at', 'sort_order', 'name', 'code'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }

        $query->orderBy($order, $typeOrder);

        return response()->jsonMacro(
            OptionValueResource::collection($query->paginate($perPage))
        );
    }

    /**
     * لیست OptionValueهای فعال یک Option برای کلاینت
     *
     * @param Request $request
     * @param Option $option
     * @return JsonResponse
     * @group OptionValue
     */
    public function clientIndex(Request $request, Option $option): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            $value = OptionValue::where('option_id', $option->id)
                ->where('is_active', 1)
                ->where('id', $id)
                ->firstOrFail();

            return response()->jsonMacro(new OptionValueResource($value));
        }

        $order     = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage   = (int) $request->query('per_page', 20);

        $allowedColumns = ['id', 'created_at', 'updated_at', 'sort_order', 'name', 'code'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $query = OptionValue::where('option_id', $option->id)
            ->where('is_active', 1)
            ->orderBy($order, $typeOrder);

        return response()->jsonMacro(
            OptionValueResource::collection($query->paginate($perPage))
        );
    }

    /**
     * ایجاد OptionValue جدید برای یک Option
     *
     * @param OptionValueRequest $request
     * @param Option $option
     * @return JsonResponse
     * @throws AuthorizationException
     * @group OptionValue
     */
    public function store(OptionValueRequest $request, Option $option): JsonResponse
    {
        $this->authorize('create', OptionValue::class);

        $value = new OptionValue();
        $value->fill($request->safe()->all());
        $value->option_id = $option->id;

        if ($value->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * بروزرسانی OptionValue
     *
     * @param OptionValueRequest $request
     * @param Option $option
     * @param OptionValue $optionValue
     * @return JsonResponse
     * @throws AuthorizationException
     * @group OptionValue
     */
    public function update(OptionValueRequest $request, Option $option, OptionValue $optionValue): JsonResponse
    {
        $this->authorize('update', OptionValue::class);

        // اطمینان از اینکه Value متعلق به همین Option است
        if ($optionValue->option_id !== $option->id) {
            abort(404);
        }

        $optionValue->fill($request->safe()->all());

        if ($optionValue->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $optionValue->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * حذف OptionValue
     *
     * @param Option $option
     * @param OptionValue $optionValue
     * @return JsonResponse
     * @throws AuthorizationException
     * @group OptionValue
     */
    public function destroy(Option $option, OptionValue $optionValue): JsonResponse
    {
        $this->authorize('delete', OptionValue::class);

        if ($optionValue->option_id !== $option->id) {
            abort(404);
        }

        if ($optionValue->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $optionValue->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
