<?php

namespace App\Services\V1\Otp;

use App\Models\User;
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class Sms implements Gateway
{
    /**
     * @param User $user
     * @return JsonResponse
     */
    public function init(User $user): JsonResponse
    {
        return $this->notify($user);
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function send(User $user): JsonResponse
    {
        return $this->notify($user);
    }

    /**
     * @param string $code
     * @param User $user
     * @return bool
     */
    public function verify(string $code, User $user): bool
    {
        if (env('APP_ENV') !== 'production' && ($user->mobile === config('app.mobile_number_test_1') || $user->mobile === config('app.mobile_number_test_2'))) {
            $user->mobile_verified_at = now();
            $user->save();
            return true;
        }

        return Verification::verifyCode($code, $user);
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    private function notify(User $user)
    {
        $now = Carbon::now();
        $activeCode = !! Verification::where('user_id', $user->id)->where('expire_at', '>', $now)->first();
        if (!$activeCode) {
            $code = Verification::generateCode($user);
            $user->notify(new \App\Notifications\V1\Otp($code, $user->mobile));
        }

        $message = empty($activeCode) ? 'sms-' . config('general.auth') : 'sms can not be sent wait and request later';

        return response()->json([
            'status' => true,
            'message' => $message,
            'two_step' => $user->two_step
        ], 200);
    }
}
