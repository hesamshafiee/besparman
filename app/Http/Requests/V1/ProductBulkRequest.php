<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProductBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // یا Policy
    }

    public function rules(): array
    {
        return [
            // لیست تصاویر ورودی
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],

            // هر محصول دقیقاً یک واریانت
            'variant_id' => ['required', 'integer', 'exists:variants,id'],

            // آدرس نود داخلی (اختیاری)
            'address' => ['nullable', 'string', 'max:100'],

            // اختیاری‌ها
            'name'   => ['nullable', 'string', 'max:200'],
            'price'  => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:0,1'],

            // تنظیمات جایگذاری طرح
            'settings' => ['required'], // string(JSON) or array
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // settings را اگر رشته JSON بود، دیکد کن
        if (isset($data['settings']) && is_string($data['settings'])) {
            $decoded = json_decode($data['settings'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['settings'] = $decoded;
            }
        }
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            $data['settings'] = [];
        }

        // پیش‌فرض‌های settings (در صورت نبود)
        $data['settings'] = array_merge([
            'print_x'        => 0,
            'print_y'        => 0,
            'print_width'    => 1000,
            'print_height'   => 1000,
            'print_rotation' => 0,
            'fit_mode'       => 'contain',
            // اختیاری:
            // 'canvas_width' => null,
            // 'canvas_height'=> null,
            // 'preview_bg'   => null,
        ], $data['settings']);

        // اگر price به صورت string عددی آمد، intش کن
        if (isset($data['price']) && is_string($data['price']) && is_numeric($data['price'])) {
            $data['price'] = (int) $data['price'];
        }

        $this->replace($data);
    }
}
