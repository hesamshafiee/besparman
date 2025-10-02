<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class VersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => 'required|string',
            'type' => 'required|string|in:admin,panel',
            'title' => 'required|string',
            'description' => 'required|string',
            'status' => 'boolean',
        ];
    }
}
