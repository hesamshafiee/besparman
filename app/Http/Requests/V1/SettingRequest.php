<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sms' => 'boolean',
            'email' => 'boolean',
            'auth' => 'in:otp,otpOrPassword,usernamePassword',
            'otp' => 'in:sms,google2fa',
            'jwt_expiration_time' => 'integer|min:1|max:1000',
            'front' => 'json',
            'status' => 'boolean',
        ];
    }
}
