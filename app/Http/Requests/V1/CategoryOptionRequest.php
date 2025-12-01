<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CategoryOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'options'                 => ['required', 'array', 'min:1'],
            'options.*.option_id'     => ['required', 'integer', 'exists:options,id'],
            'options.*.is_required'   => ['nullable', 'boolean'], // null => ارث‌بری از option
            'options.*.is_active'     => ['nullable', 'boolean'],
            'options.*.sort_order'    => ['nullable', 'integer', 'min:0'],
        ];
    }
}
