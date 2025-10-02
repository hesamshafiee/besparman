<?php

namespace App\Http\Requests\V1\Financial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountRequest extends FormRequest
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
            'code' =>  ['string', 'max:255', Rule::unique('discounts', 'code')->ignore($this->id)],
            'type' => [Rule::requiredIf(is_null($this->discount)), 'in:money,percent'],
            'value' => [Rule::requiredIf(is_null($this->discount)), 'numeric'],
            'users.*' => 'numeric|exists:users,id',
            'products.*' => 'numeric|exists:products,id',
            'min_purchase' => 'numeric',
            'max_purchase' => 'numeric',
            'count' => 'numeric',
            'status' => 'boolean',
            'reusable' => 'boolean',
            'expire_at' => 'date',
        ];
    }
}
