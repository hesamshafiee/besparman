<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class LogisticRequest extends FormRequest
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
            'city' => 'required|string|min:3|max:128',
            'province' => 'required|string|min:3|max:128',
            'country' => 'required|string|min:3|max:128',
            'type' => 'required|string|max:128',
            'capacity' => 'digits_between:1,250',
            'price' => 'required|numeric',
            'min_price_for_free_delivery' => 'numeric',
            'start_delivery_after_day' => 'digits_between:1,250',
            'start_delivery_after_time' => 'digits_between:1,250',
            'start_time' => 'digits_between:1,24',
            'end_time' => 'digits_between:1,24',
            'divide_time' => 'digits_between:1,24',
            'is_active_in_holiday' => 'boolean',
            'days_not_working' => 'json',
            'status' => 'boolean',
            'default' => 'boolean',
            'is_capital' => 'boolean',
            'description' => 'string|max:250',
        ];
    }
}
