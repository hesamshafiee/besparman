<?php

namespace App\Http\Requests\V1\Esaj;

use App\Models\Operator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopUp extends FormRequest
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
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        $this->merge(['operator' => $this->route('operator')]);

        return [
            'operator' => ['required', 'string', Rule::in([
                Operator::APTEL,
                Operator::IRANCELL,
                Operator::RIGHTEL,
                Operator::SHATEL,
                Operator::MCI
            ])],
            'mobileNumber' => ['required', 'numeric', 'digits_between:12,12'],
            'price' => 'required|string',
            'product' => ['required', 'string'],
            'order_id' => 'required|numeric',
            'type' => ['required', 'string'],
            'national_code' => ['required', 'string'],
            'store_name' => ['required', 'string'],
            'ext_id' => 'required|numeric',
        ];
    }
}
