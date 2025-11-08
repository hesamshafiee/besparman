<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MockupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'category_id'   => ['required','integer','exists:categories,id'],
            'name'          => ['required','string','max:150'],
            'slug'          => [
                'nullable','string','max:180',
                Rule::unique('mockups','slug')->ignore($id)
            ],

            'canvas_width'  => ['required','integer','min:100'],
            'canvas_height' => ['required','integer','min:100'],
            'dpi'           => ['nullable','integer','min:72','max:600'],

            'print_x'       => ['required','integer','min:0'],
            'print_y'       => ['required','integer','min:0'],
            'print_width'   => ['required','integer','min:1'],
            'print_height'  => ['required','integer','min:1'],
            'print_rotation'=> ['nullable','integer','between:0,359'],
            'fit_mode'      => ['required','in:contain,cover,stretch'],

            'layers'        => ['nullable'],
            'layers.base'   => ['nullable','string','max:255'],
            'layers.overlay'=> ['nullable','string','max:255'],
            'layers.shadow' => ['nullable','string','max:255'],
            'layers.mask'   => ['nullable','string','max:255'],

            'preview_bg'    => ['nullable','string','max:15'], // '#FFFFFF' یا 'transparent'
            'is_active'     => ['nullable','boolean'],
            'sort'          => ['nullable','integer','min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['layers']) && is_string($data['layers'])) {
            $decoded = json_decode($data['layers'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['layers'] = $decoded;
            }
        }

        if (!isset($data['fit_mode'])) {
            $data['fit_mode'] = 'contain';
        }

        $this->replace($data);
    }
}
