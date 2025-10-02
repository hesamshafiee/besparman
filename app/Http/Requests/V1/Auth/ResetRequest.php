<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetRequest extends FormRequest
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
        if ($this->path() === 'api/auth/reset-google2fa') {
            return [
                'mobile' => ['required', 'numeric', 'digits_between:12,12'] ,
                'code' => ['min:6', 'max:6'],
            ];
        }

        return [
            'mobile' => ['required', 'numeric', 'digits_between:12,12'] ,
            'password' => ['required_with:code', 'string', Password::min(8)->letters()->mixedCase()->numbers()],
            'code' => ['required_with:password', 'min:6', 'max:6'],
        ];
    }
}
