<?php

namespace App\Services\V1\Otp;


class OtpFactory
{
    const TYPE_GOOGLE_2FA = 'google2fa';
    const TYPE_SMS = 'sms';

    /**
     * @param string $type
     * @return Google2fa|Sms|null
     */
    public function creator(string $type = self::TYPE_SMS)
    {
        $otpObject = null;

        if ($type === self::TYPE_GOOGLE_2FA) {
            $otpObject = new Google2fa();
        } elseif ($type === self::TYPE_SMS) {
            $otpObject = new Sms();
        }

        return $otpObject;
    }
}
