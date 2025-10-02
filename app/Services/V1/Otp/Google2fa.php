<?php

namespace App\Services\V1\Otp;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class Google2fa implements Gateway
{
    /**
     * @param User $user
     * @return JsonResponse
     */
    public function init(User $user): JsonResponse
    {
        // Initialise the 2FA class
        $google2fa = app('pragmarx.google2fa');

        // Add the secret key to the registration data
        $secret = $google2fa->generateSecretKey();

        // Generate the QR image. This is the image the user will scan with their app
        // to set up two factor authentication
        $QR_Image = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->mobile,
            $secret
        );

        $user->google2fa = $secret;

        if ($user->save()) {
            return response()->json([
                'status' => true,
                'message' => 'google2fa-' . config('general.auth'),
                'two_step' => $user->two_step,
                'secret' => $secret,
                'QR_image' => $QR_Image,
            ], 200);
        }

        return response()->serverError(__('auth.somethingWrong'));
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function send(User $user): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => __('auth.google2faSendMessage'),
            'auth' => config('general.auth'),
            'otp' => config('general.otp'),
        ], 200);
    }

    /**
     * @param string $code
     * @param User $user
     * @return bool
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     */
    public function verify(string $code, User $user): bool
    {
        if (env('APP_ENV') !== 'production' && ($user->mobile === config('app.mobile_number_test_1') || $user->mobile === config('app.mobile_number_test_2'))) {
            $user->mobile_verified_at = now();
            $user->save();
            return true;
        }

        $google2fa =  new \PragmaRX\Google2FA\Google2FA();
        return $google2fa->verifyKey($user->google2fa, $code);
    }
}
