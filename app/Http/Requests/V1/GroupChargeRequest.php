<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class GroupChargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_numbers' => 'json|required',
            //'price' => ['required', 'numeric', $min, $max],
            'taken_value' => ['numeric'],
            'refCode' => ['string'],
            'type' => ['string'],
            'ext_id' => ['numeric', 'in:59,19']
        ];
    }
}
