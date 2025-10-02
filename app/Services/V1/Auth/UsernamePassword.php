<?php

namespace App\Services\V1\Auth;

use App\Models\User;
use App\Services\V1\Otp\OtpFactory;
use App\Services\V1\Otp\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class UsernamePassword implements Gateway
{
    /**
     * @param array $credentials
     * @param User $user
     * @return JsonResponse
     */
    public function login(User $user, string $password = null, bool $otpForce = null, string $twoStepType = null) :JsonResponse
    {
        if (is_null($user->mobile_verified_at)) {
            $otpService = new OtpService($user);
            return $otpService->serviceController();
        } elseif (is_null($password)) {
            return response()->ok(__('auth.enterPass'));
        } elseif ($user->passwordCheck($password)) {
            if ($user->two_step) {
                $otpService = new OtpService($user, false, $twoStepType);
                return $otpService->serviceController();
            } else {
                return $user->generateToken();
            }
        }

        return response()->unauthorized(__('auth.authNotMatch'));
    }

    /**
     * @param string $mobile
     * @param string $password
     * @return JsonResponse
     */
    public function register(string $mobile) :JsonResponse
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
        $otpService = new OtpService($user, false, $twoStepType);

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
        if ($code && $password) {
            $otpService = new OtpService($user);

            if ($otpService->serviceController($code)) {
                $user->password = Hash::make($password);

                if ($user->save()) {
                    return $user->generateToken();
                }

                return response()->serverError(__('auth.somethingWrong'));
            }

            return response()->unauthorized(__('auth.otpNotMatch'));

        } else {
            $otpService = new OtpService($user);
            return $otpService->serviceController();
        }
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
        if ($code) {
            $otpService = new OtpService($user, true);

            if ($otpService->serviceController($code)) {
                $otpService = new OtpService($user, false, OtpFactory::TYPE_GOOGLE_2FA);
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
        $user = Auth::user();

        if ($user && empty($user->password)) {
            $user->password = Hash::make($password);

            if ($user->save()) {
                return response()->ok(__('general.savedSuccessfully'));
            }
        }

        return response()->serverError(__('auth.somethingWrong'));
    }
}
