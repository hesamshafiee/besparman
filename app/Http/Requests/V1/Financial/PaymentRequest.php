<?php

namespace App\Http\Requests\V1\Financial;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->path() === 'api/payment/bank/increase') {
            return [
                'value' => 'required|integer|min:200000|max:2000000000',
                'bank' => 'string|in:saman,mellat',
                'mobile' => ['required', 'numeric', 'digits_between:12,12'],
                'return_url' => ['required', 'string', 'max:300']
            ];
        }

        return [
            'value' => 'required|integer|min:200000|max:2000000000',
            'image.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
