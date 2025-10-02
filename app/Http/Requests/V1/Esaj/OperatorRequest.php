<?php

namespace App\Http\Requests\V1\Esaj;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperatorRequest extends FormRequest
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
            'title' => 'string|min:3|max:255',
            'credit_cell_internet' => 'boolean',
            'credit_td_lte_internet' => 'boolean',
            'credit_cell_direct_charge' => 'boolean',
            'credit_cell_internet_direct_charge' => 'boolean',
            'permanent_cell_internet' => 'boolean',
            'permanent_td_lte_internet' => 'boolean',
            'permanent_cell_direct_charge' => 'boolean',
            'permanent_cell_internet_direct_charge' => 'boolean',
            'bill' => 'boolean',
            'radin_status' => 'boolean',
            'radin_limit' => 'numeric',
            'igap_limit' => 'numeric',
            'radin_limit_package' => 'numeric',
            'igap_limit_package' => 'numeric',
            'status' => 'numeric|digits_between:0,1',
        ];
    }
}
