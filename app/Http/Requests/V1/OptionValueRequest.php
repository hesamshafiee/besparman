<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OptionValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Option والد از Route Model Binding
        $option = $this->route('option');

        // خود رکورد OptionValue (برای ignore در unique)
        $valueModel = $this->route('optionValue')
            ?: $this->route('value')
            ?: null;

        $id = $valueModel?->id ?? $this->route('id');

        // قانون unique برای code، scoped روی همان option
        $uniqueCodeRule = Rule::unique('option_values', 'code')->ignore($id);
        if ($option) {
            $uniqueCodeRule = $uniqueCodeRule->where(fn ($q) => $q->where('option_id', $option->id));
        }

        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'nullable',          // اینجا دیگر required نیست تا در PATCH لازم نباشد
                'string',
                'max:120',
                'alpha_dash',
                $uniqueCodeRule,
            ],

            'is_active' => ['nullable', 'boolean'],

            'sort_order' => ['nullable', 'integer', 'min:0'],

            'meta' => ['nullable', 'array'],

            // فیلدهای متداول داخل meta (اختیاری)
            'meta.color'           => ['sometimes', 'string', 'max:20'],
            'meta.price_modifier'  => ['sometimes', 'numeric'],
            'meta.weight_modifier' => ['sometimes', 'numeric'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // اگر meta به صورت رشته JSON ارسال شده بود، تبدیلش کن به array
        if (isset($data['meta']) && is_string($data['meta'])) {
            $decoded = json_decode($data['meta'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['meta'] = $decoded;
            }
        }

        $this->replace($data);
    }
}
