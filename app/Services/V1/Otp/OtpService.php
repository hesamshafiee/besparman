<?php

namespace App\Services\V1\Otp;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

/**
 * Class AuthService
 */

class OtpService
{
    protected $gateway;
    protected $envType;
    protected $user;

    public function __construct(User $user, $sms = false, $envType = null)
    {
        $this->user = $user;
        $factory = new OtpFactory();
        if ($envType) {
            $this->envType = strtolower($envType);
        } elseif ($sms || is_null($this->user->mobile_verified_at)) {
            $this->envType = OtpFactory::TYPE_SMS;
        } else {
            $this->envType = config('general.otp');
        }
        $gateway = $factory->creator($this->envType);
        $this->gateway = $gateway;
    }

    /**
     * @param string|null $code
     * @param bool $init
     * @return JsonResponse|bool
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function serviceController(string $code = null, bool $init = false) : JsonResponse | bool
    {
        if ($init || ($this->envType === OtpFactory::TYPE_GOOGLE_2FA && is_null($this->user->google2fa))) {
            return $this->gateway->init($this->user);
        } elseif (is_null($code)) {
            return $this->gateway->send($this->user);
        } else {
            return $this->gateway->verify($code, $this->user);
        }
    }
}
