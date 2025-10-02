<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AclRequest extends FormRequest
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
        if ($this->path() === 'api/acl/create-role' || (str_contains($this->path(), 'api/acl/update-role') && $this->method() === "PATCH")) {
            return [
                'role' =>  [
                    'required', 'string', 'min:3', 'max:255',
                    Rule::unique('roles', 'name')->ignore($this->id)
                ],
            ];
        }

        return [
            'role' => 'required|string',
            'permissions' => 'required_without:user|array',
            'user' => 'required_without:permissions|numeric',
        ];
    }
}
