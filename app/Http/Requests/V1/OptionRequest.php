<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Option;

class OptionRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        // اگر در حالت update هستیم
        $optionId = $this->route('option')?->id;

        return [
            'name' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:191',
            ],

            'code' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('options', 'code')->ignore($optionId),
            ],

            'type' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                Rule::in(['select', 'multi_select', 'text', 'number', 'swatch']),
            ],

            'is_required' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'meta' => ['nullable', 'array'],
            
            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }
}
