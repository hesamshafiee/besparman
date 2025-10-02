<?php

namespace App\Http\Requests\V1;

use App\Models\Prize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrizeRequest extends FormRequest
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
            'name' => [Rule::requiredIf(!$this->has('product_id')), 'string', 'min:3', 'max:255'],
            'description' => 'string|min:3|max:65535',
            'point' => 'required|integer|min:1|max:1000000',
            'type' => [Rule::requiredIf(!$this->has('product_id')), 'string', Rule::in([
                Prize::TYPE_CELL_INTERNET_PACKAGE,
                Prize::TYPE_TD_LTE_INTERNET_PACKAGE,
                Prize::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                Prize::TYPE_AMAZING_CELL_DIRECT_CHARGE,
                Prize::TYPE_CELL_DIRECT_CHARGE,
                Prize::TYPE_PHYSICAL,
                Prize::TYPE_INCREASE_PRIZE,
                Prize::TYPE_DISCOUNT
            ])],
            'price' => [Rule::requiredIf(!$this->has('product_id') && !in_array($this->type, [Prize::TYPE_DISCOUNT, Prize::TYPE_PHYSICAL])), 'numeric'],
            'operator_id' => [Rule::requiredIf(!$this->has('product_id') && !in_array($this->type, [Prize::TYPE_DISCOUNT, Prize::TYPE_PHYSICAL, Prize::TYPE_INCREASE_PRIZE])), 'numeric', 'exists:operators,id'],
            'product_id' => ['numeric', 'exists:products,id'],
            'profile_id' => [Rule::requiredIf(!$this->has('product_id') && !in_array($this->type, [Prize::TYPE_DISCOUNT, Prize::TYPE_PHYSICAL, Prize::TYPE_INCREASE_PRIZE])), 'string'],
            'ext_id' => [Rule::requiredIf(!in_array($this->type, [Prize::TYPE_DISCOUNT, Prize::TYPE_PHYSICAL, Prize::TYPE_INCREASE_PRIZE])), 'string'],
            'operator_type' => ['numeric'],
            'count' => ['numeric'],
            'url' => ['string', 'max:255'],
            'tags.*' => ['string', 'max:100'],
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
