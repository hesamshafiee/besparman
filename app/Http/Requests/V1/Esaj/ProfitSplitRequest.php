<?php

namespace App\Http\Requests\V1\Esaj;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfitSplitRequest extends FormRequest
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
        return [
            'title' => 'required|string|min:3|max:100',
            'seller_profit' => 'required|numeric|min:1|max:100',
            'profit_id' => 'required|numeric|exists:profits,id',
        ];
    }
}
