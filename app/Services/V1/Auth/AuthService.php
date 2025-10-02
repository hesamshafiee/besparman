<?php

namespace App\Services\V1\Auth;

use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

/**
 * Class AuthService
 */
class AuthService
{
    protected Gateway $gateway;

    public string|null $mobile = null;
    public string|null $code = null;
    public string|null $password = null;
    public string|null $twoStepType = null;
    public bool $otpForce = false;

    public function __construct()
    {
        $factory = new AuthFactory();
        $this->gateway = $factory->creator(config('general.auth'));
    }

    /**
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function serviceController(): JsonResponse
    {
        if ($this->code) {
            $user = User::where('mobile', $this->mobile)->firstOrFail();
            return $this->gateway->otp($this->code, $user, $this->twoStepType);
        } else {
            $user = User::where('mobile', $this->mobile)->withTrashed()->first();
            if ($user) {
                if ($user->trashed()) {
                    if ($user->deleted_at->eq(Carbon::create(2012, 12, 12, 12, 12, 12))) {
                        $response = Http::get('https://esaj.ir/site/getuserbyid?id=' . $user->id);

                        $walletValue  = $response->json('wallet_value');
                        $point  = (int) ceil($response->json('point'));
                        $batchArray = [];

                        foreach ($response->json('phone_book') as $index => $number) {
                            $batchArray[$index]['name'] = $number['name'];
                            $batchArray[$index]['phone_number'] = $number['mobile'];
                            $batchArray[$index]['user_id'] = $user->id;
                            $batchArray[$index]['last_settings'] = '{}';
                        }

                        $walletTransaction = WalletTransaction::where('user_id', $user->id)->firstOrFail();
                        $walletTransaction->value = $walletValue;
                        $walletTransaction->wallet_value_after_transaction = $walletValue;
                        $walletTransaction->sign = $walletTransaction->sign();

                        return DB::transaction(function () use ($user, $batchArray, $point, $walletValue, $walletTransaction) {
                            $user->points = $point;
                            $user->deleted_at = null;
                            $wallet = $user->wallet;
                            $wallet->value = $walletValue;
                            $chunkSize = 100;
                            $insertStatus = true;

                            foreach (array_chunk($batchArray, $chunkSize) as $chunk) {
                                if (DB::table('phone_books')->insertOrIgnore($chunk)) {
                                    $insertStatus = true;
                                } else {
                                    $insertStatus = false;
                                }
                            }

                            if ($insertStatus && $user->save() && $walletTransaction->save() && $wallet->save()) {
                                return $this->gateway->login($user, $this->password, $this->otpForce, $this->twoStepType);
                            }

                            return response()->serverError(__('general.somethingWrong'));
                        });
                    }
                    return response()->forbidden(__('auth.deletedUserError'));
                } else {
                    return $this->gateway->login($user, $this->password, $this->otpForce, $this->twoStepType);
                }
            } else {
                return $this->gateway->register($this->mobile);
            }
        }
    }

    /**
     * @return JsonResponse
     */
    public function resetPassword() :JsonResponse
    {
        $user = User::where('mobile', $this->mobile)->firstOrFail();

        if (isset($this->code) && isset($this->password)) {
            return $this->gateway->resetPassword($user, $this->code, $this->password);
        } else {
            return $this->gateway->resetPassword($user);
        }
    }

    /**
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function resetGoogle2fa() :JsonResponse
    {
        $user = User::where('mobile', $this->mobile)->firstOrFail();
        return $this->gateway->resetGoogle2fa($user, $this->code);
    }

    /**
     * @return JsonResponse
     */
    public function setPassword() :JsonResponse
    {
        return $this->gateway->setPassword($this->password);
    }
}
