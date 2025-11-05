<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // می‌تونی اینجا از policy هم استفاده کنی
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'parent_id' => 'nullable|exists:categories,id',
            'data' => 'nullable|json', 
            'images.*' => 'nullable|image|max:2048',
        ];
    }
}
