<?php

namespace App\Services\V1\Auth;


class AuthFactory
{
    const TYPE_OTP = 'otp';
    const TYPE_USERNAME_PASSWORD = 'usernamePassword';
    const TYPE_OTP_OR_PASSWORD = 'otpOrPassword';

    /**
     * @param string $type
     * @return Otp|OtpOrPassword|UsernamePassword|null
     */
    public function creator(string $type = self::TYPE_USERNAME_PASSWORD)
    {
        $authMethod = null;

        if ($type === self::TYPE_OTP) {
            $authMethod = new Otp();
        } elseif ($type === self::TYPE_USERNAME_PASSWORD) {
            $authMethod = new UsernamePassword();
        } elseif ($type === self::TYPE_OTP_OR_PASSWORD) {
            $authMethod = new OtpOrPassword();
        }

        return $authMethod;
    }
}
