<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\VariantResource;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\OptionValue;
use Illuminate\Support\Facades\DB;


class VariantController extends Controller
{
    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Variant
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id          = (int) $request->query('id', 0);
        $categoryId  = (int) $request->query('category_id', 0);
        $order       = $request->query('order', 'id');
        $typeOrder   = strtolower($request->query('type_order', 'desc'));
        $perPage     = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at', 'add_price', 'stock'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (!in_array($typeOrder, ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $base = Variant::with(['category'])
            ->whereHas('category', function ($q) {
                $q->where('show_in_work', 1);
        });



        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new VariantResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(VariantResource::collection($paginator));
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Variant
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Variant::class);

        $id          = (int) $request->query('id', 0);
        $categoryId  = (int) $request->query('category_id', 0);
        $order       = $request->query('order', 'id');
        $typeOrder   = strtolower($request->query('type_order', 'desc'));
        $perPage     = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at', 'add_price', 'stock'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }
        if (!in_array($typeOrder, ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $base = Variant::query();

        if ($categoryId) {
            $base->where('category_id', $categoryId);
        }

        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new VariantResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(VariantResource::collection($paginator));
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Variant
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Variant::class);

        $validated = $request->validate([
            'category_id'      => ['required', 'integer', 'exists:categories,id'],
            'stock'            => ['required', 'integer', 'min:0'],
            'add_price'        => ['required', 'numeric', 'min:0'],
            'is_active'        => ['sometimes', 'boolean'],

            // اینجا: option_value ها
            'option_value_ids'   => ['required', 'array', 'min:1'],
            'option_value_ids.*' => ['integer', 'exists:option_values,id'],
        ]);

        return DB::transaction(function () use ($validated) {

            $optionValues = OptionValue::whereIn('id', $validated['option_value_ids'])
                ->with('option')
                ->get();

            $sku = $this->makeSkuFromOptionValues(
                $validated['category_id'],
                $optionValues
            );

            $variant = new Variant();
            $variant->category_id = $validated['category_id'];
            $variant->sku         = $sku;
            $variant->stock       = $validated['stock'];
            $variant->add_price   = $validated['add_price'];
            $variant->is_active   = (bool) ($validated['is_active'] ?? true);

            if (! $variant->save()) {
                return response()->serverError(__('general.somethingWrong'));
            }

            // ۴) ساختن رکوردهای pivot (variant_option_value)
            $variant->optionValues()->sync($validated['option_value_ids']);

            return response()->ok(__('general.savedSuccessfully'));
        });
    }

    /**
     *
     * @param Request $request
     * @param Variant $variant
     * @return JsonResponse
     * @group Variant
     */
    public function update(Request $request, Variant $variant): JsonResponse
    {
        $this->authorize('show', Variant::class);

        $validated = $request->validate([
            'category_id'      => ['sometimes', 'integer', 'exists:categories,id'],
            'stock'            => ['sometimes', 'integer', 'min:0'],
            'add_price'        => ['sometimes', 'numeric', 'min:0'],
            'is_active'        => ['sometimes', 'boolean'],

            'option_value_ids'   => ['sometimes', 'array', 'min:1'],
            'option_value_ids.*' => ['integer', 'exists:option_values,id'],
        ]);

        return DB::transaction(function () use ($validated, $variant) {

            if (array_key_exists('category_id', $validated)) {
                $variant->category_id = $validated['category_id'];
            }
            if (array_key_exists('stock', $validated)) {
                $variant->stock = $validated['stock'];
            }
            if (array_key_exists('add_price', $validated)) {
                $variant->add_price = $validated['add_price'];
            }
            if (array_key_exists('is_active', $validated)) {
                $variant->is_active = (bool) $validated['is_active'];
            }

            if (array_key_exists('option_value_ids', $validated)) {

                $optionValues = OptionValue::whereIn('id', $validated['option_value_ids'])
                    ->with('option')
                    ->get();

                $categoryIdForSku = $variant->category_id;

                if (array_key_exists('category_id', $validated)) {
                    $categoryIdForSku = $validated['category_id'];
                }

                $variant->sku = $this->makeSkuFromOptionValues(
                    $categoryIdForSku,
                    $optionValues
                );

                $variant->optionValues()->sync($validated['option_value_ids']);
            }

            if (! $variant->save()) {
                return response()->serverError(__('general.somethingWrong'));
            }

            return response()->ok(__('general.updatedSuccessfully'));
        });
    }

    /**
     * حذف واریانت (فقط ادمین)
     *
     * @param Variant $variant
     * @return JsonResponse
     * @group Variant
     */
    public function destroy(Variant $variant): JsonResponse
    {
        $this->authorize('show', Variant::class);

        if ($variant->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $variant->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }



    protected function makeSkuFromOptionValues(int $categoryId, $optionValues): string
    {
        $sorted = $optionValues->sortBy('id');

        $parts = ['C' . $categoryId];

        foreach ($sorted as $ov) {
            $optionName = $ov->option->slug ?? $ov->option->name ?? 'opt';
            $valuePart  = $ov->slug ?? $ov->value ?? $ov->id;

            $parts[] = strtoupper($optionName) . '_' . strtoupper($valuePart);
        }

        return implode('-', $parts);
    }
}
