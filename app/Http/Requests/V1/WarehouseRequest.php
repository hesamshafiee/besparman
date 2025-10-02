<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseRequest extends FormRequest
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
        return [
            'product_id' => [
                'required',
                'numeric',
                'exists:products,id',
                Rule::unique('warehouses', 'product_id')->ignore($this->route('warehouse')),
            ],
            'count' => 'required|numeric',
            'weight' => 'numeric',
            'price' => 'required|numeric',
            'expiry_date' => 'date',
            'warehouse_address' => 'string|max:500',
            'source' => 'string|max:500',
        ];
    }
}
