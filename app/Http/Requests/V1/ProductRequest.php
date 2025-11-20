<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // یا via policy
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            // روابط
            'user_id'    => ['required','integer','exists:users,id'],
            'variant_id' => ['required','integer','exists:variants,id'],
            'work_id'    => ['required','integer','exists:works,id'],

            // اطلاعات پایه
            'name'        => ['nullable','string','max:200'],
            'slug'        => [
                'nullable',
                'string',
                'max:220',
                Rule::unique('products','slug')->ignore($id)
            ],
            'name_en'     => ['nullable','string','max:200'],

            // توضیحات
            'description'       => ['nullable','string'],
            'description_full'  => ['nullable','string'],

            // قیمت/نوع
            'sku'         => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products','sku')->ignore($id)
            ],
            'price'       => ['nullable','integer','min:0'],
            'currency'    => ['nullable','string','size:3'],
            'type'        => ['nullable','string','max:50'],

            // تنظیمات فروش/نمایش
            'minimum_sale'=> ['nullable','integer','min:1'],
            'dimension'   => ['nullable','string','max:50'],
            'score'       => ['nullable','integer','between:0,100'],
            'status'      => ['nullable','integer','between:0,5'],
            'sort'        => ['nullable','integer','min:0'],

            // مسیر فایل‌ها (معمولاً توسط سیستم پر می‌شود)
            'original_path' => ['nullable','string','max:255'],
            'preview_path'  => ['nullable','string','max:255'],

            // JSON / آرایه‌ها
            'settings'    => ['nullable'], // string(json) یا array؛ در prepare تبدیل می‌کنیم
            'options'     => ['nullable'],
            'meta'        => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // تبدیل رشته‌های JSON به آرایه
        foreach (['settings','options','meta'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                }
            }
        }

        // اگر slug خالی بود و name داشتیم، خودکار بسازیم
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = str($data['name'])->slug('-') . '-' . str()->random(4);
        }

        // user_id را اگر ندادند، از کاربر لاگین شده پر کن
        if (empty($data['user_id']) && auth()->check()) {
            $data['user_id'] = auth()->id();
        }

        $this->replace($data);
    }
}
