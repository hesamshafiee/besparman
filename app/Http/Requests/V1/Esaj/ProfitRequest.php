<?php

namespace App\Http\Requests\V1\Esaj;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfitRequest extends FormRequest
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
            'title' => 'required|string|min:3|max:255',
            'profit' => 'required|numeric|min:1|max:100',
            'status' => 'numeric|digits_between:0,1',
            'operator_id' => 'required|numeric|exists:operators,id',
            'type' => 'required|string|min:3|max:255',
        ];
    }
}
