<?php

namespace App\Http\Requests\V1;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        if (str_contains($this->path(), 'api/products/options')) {
            return [
                'option' => 'required|string',
                'value' => 'required|string',
                'price' => 'numeric',
                'price_type' => 'required_with:price|string|in:increase,decrease',
                'price_type2' => 'required_with:price|string|in:percent,cash',
                'image' => 'string'
            ];
        }

        if (str_contains($this->path(), 'api/products/assign-category')) {
            return [
                'category_id' => 'required_with:address|numeric',
                'address' => 'required_with:category_id|string|max:50'
            ];
        }
        if (str_contains($this->path(), 'api/products/bulk-update')) {
            return [
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.sku' => [
                    'sometimes',
                    'string',
                    'min:3',
                    'max:100',
                    function ($attribute, $value, $fail) {
                        // استخراج index آیتم
                        preg_match('/products\.(\d+)\.sku/', $attribute, $matches);
                        $index = $matches[1] ?? null;
                        if ($index !== null) {
                            $id = request()->input("products.$index.id");
                            if (\App\Models\Product::where('sku', $value)->where('id', '<>', $id)->exists()) {
                                $fail("The SKU '$value' has already been taken.");
                            }
                        }
                    }
                ],
                'products.*.name' => 'sometimes|string|min:3|max:255',
                'products.*.name_en' => 'sometimes|string|min:3|max:255',
                'products.*.description' => 'sometimes|string|max:255',
                'products.*.description_full' => 'sometimes|string|max:1000',
                'products.*.price' => 'sometimes|numeric',
                'products.*.second_price' => 'sometimes|numeric',
                'products.*.showable_price' => 'sometimes|numeric',
                'products.*.type' => ['sometimes', 'string', Rule::in([
                    Product::TYPE_CELL_INTERNET_PACKAGE,
                    Product::TYPE_TD_LTE_INTERNET_PACKAGE,
                    Product::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                    Product::TYPE_CELL_AMAZING_DIRECT_CHARGE,
                    Product::TYPE_CELL_DIRECT_CHARGE,
                    Product::TYPE_CART,
                    Product::TYPE_CARD_CHARGE
                ])],
                'products.*.minimum_sale' => 'sometimes|numeric',
                'products.*.dimension' => 'sometimes|string|min:3|max:50',
                'products.*.images.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
                'products.*.deliverable' => 'sometimes|boolean',
                'products.*.status' => 'sometimes|boolean',
                'products.*.third_party_id' => 'sometimes|string|max:255',
                'products.*.operator_id' => 'sometimes|numeric|exists:operators,id',
                'products.*.order' => 'sometimes|numeric',
                'products.*.period' => 'sometimes|numeric',
                'products.*.sim_card_type' => 'sometimes|string',
                'products.*.private' => 'sometimes|boolean',
                'products.*.profile_id' => 'sometimes|string',
            ];
        }

            return [
                'sku' => ['string', 'min:3', 'max:100',  Rule::unique('products', 'sku')->ignore($this->id)],
                'name' => 'required|string|min:3|max:255',
                'name_en' => 'string|min:3|max:255',
                'description' => 'string|max:255',
                'description_full' => 'string|max:1000',
                'price' => 'required|numeric',
                'second_price' => 'numeric',
                'showable_price' => 'numeric',
                'type' => ['required', 'string', Rule::in([
                    Product::TYPE_CELL_INTERNET_PACKAGE,
                    Product::TYPE_TD_LTE_INTERNET_PACKAGE,
                    Product::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                    Product::TYPE_CELL_AMAZING_DIRECT_CHARGE,
                    Product::TYPE_CELL_DIRECT_CHARGE,
                    Product::TYPE_CART,
                    Product::TYPE_CARD_CHARGE
                    ])],
                'minimum_sale' => 'numeric',
                'dimension' => 'string|min:3|max:50',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
                'deliverable' => 'boolean',
                'status' => 'boolean',
                'third_party_id' => 'string|max:255',
                'operator_id' => 'numeric|exists:operators,id',
                'order' => 'numeric',
                'period' => 'numeric',
                'sim_card_type' => 'string',
                'private' => 'boolean',
                'profile_id' => 'string'
            ];

    }
}
