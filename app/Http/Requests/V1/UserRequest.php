<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        if ($this->path() === 'api/users/add-images') {
            return [
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
            ];
        }

        return [
            'mobile' => ['required', 'numeric', 'digits_between:12,12', Rule::unique('users', 'mobile')->ignore(optional($this->route('user'))->id)],
            'email' => 'email',
            'type' => 'string|in:panel,webservice,admin,esaj,ordinary',
            'private' => 'boolean',
            'profile_confirm' => 'boolean',
            'name' => 'string|nullable',
            'two_step' => 'boolean',
        ];
    }
}
