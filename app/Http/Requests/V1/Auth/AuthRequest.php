<?php

namespace App\Http\Requests\V1\Auth;

use App\Services\V1\Auth\AuthFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AuthRequest extends FormRequest
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
        if (config('general.auth') === AuthFactory::TYPE_OTP) {
            return [
                'mobile' => ['required', 'numeric', 'digits_between:12,12'] ,
                'code' => ['min:6', 'max:6'],
            ];
        }

        return [
            'mobile' => ['required', 'numeric', 'digits_between:12,12'] ,
            'password' => ['string', Password::min(8)->letters()->mixedCase()->numbers()],
            'code' => ['min:6', 'max:6'],
            'otpForce' => ['boolean'],
            'twoStepType' => ['string', 'in:google2fa,sms'],
        ];
    }
}
