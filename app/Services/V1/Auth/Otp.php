<?php

namespace App\Services\V1\Auth;

use App\Models\User;
use App\Services\V1\Otp\OtpFactory;
use App\Services\V1\Otp\OtpService;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class Otp implements Gateway
{
    /**
     * @param array $credentials
     * @param User $user
     * @return JsonResponse
     */
    public function login(User $user, string $password = null, bool $otpForce = null, string $twoStepType = null) : JsonResponse
    {
        $otpService = new OtpService($user);
        return $otpService->serviceController();
    }

    /**
     * @param string $mobile
     * @param string|null $password
     * @return JsonResponse
     */
    public function register(string $mobile) : JsonResponse
    {
        $oldUser = User::withTrashed()->where('mobile', $mobile)->first();

        if (!$oldUser) {
            $newUser = new User();
            $newUser->mobile = $mobile;

            if ($newUser->save()) {
                $otpService = new OtpService($newUser);
                return $otpService->serviceController(null, true);
            }

            return response()->serverError(__('auth.somethingWrong'));
        }

        return response()->unprocessable(__('auth.repeatedMobile'));
    }

    /**
     * @param string $code
     * @param User $user
     * @return JsonResponse
     */
    public function otp(string $code, User $user, string $twoStepType = null) : JsonResponse
    {
        $otpService = new OtpService($user);

        if ($otpService->serviceController($code)) {
            return $user->generateToken();
        }

        return response()->unauthorized(__('auth.otpNotMatch'));
    }

    /**
     * @param User $user
     * @param string|null $code
     * @param string|null $password
     * @return JsonResponse
     */
    public function resetPassword(User $user, string $code = null, string $password = null): JsonResponse
    {
        return response()->ok(__('auth.noPassReset'));
    }

    /**
     * @param User $user
     * @param string|null $code
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function resetGoogle2fa(User $user, string $code = null): JsonResponse
    {
        if (config('general.otp') !== OtpFactory::TYPE_GOOGLE_2FA) {
            return response()->ok(__('auth.noGoogle2faReset'));
        }

        if ($code) {
            $otpService = new OtpService($user, true);

            if ($otpService->serviceController($code)) {
                $otpService = new OtpService($user);
                return $otpService->serviceController(null, true);
            }

            return response()->unauthorized(__('auth.otpNotMatch'));

        } else {
            $otpService = new OtpService($user, true);
            return $otpService->serviceController();
        }
    }

    /**
     * @param string $password
     * @return JsonResponse
     */
    public function setPassword(string $password): JsonResponse
    {
        return response()->ok(__('auth.noPassSet'));
    }
}
