<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'parent_id' => 'nullable|exists:categories,id',
            'data' => 'nullable|json', 
            'default_setting' => 'nullable|json', 
            'status' => 'nullable|string|max:150', 
            'images.*' => 'nullable|image|max:2048',
        ];
    }
}
