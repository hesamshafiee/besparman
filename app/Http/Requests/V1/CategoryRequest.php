<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => [Rule::requiredIf(is_null($this->id)), 'string', 'min:3', 'max:100',  Rule::unique('categories', 'name')->ignore($this->id)],
            'data' => 'required|json',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ];
    }
}
