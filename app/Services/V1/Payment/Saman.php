<?php

namespace App\Services\V1\Payment;

use App\Models\Payment;
use App\Notifications\V1\MailSystem;
use App\Notifications\V1\SmsSystem;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SoapClient;
use SoapFault;

class Saman implements Gateway
{
    /**
     * @param int $price
     * @param string $resnumber
     * @param string $returnUrl
     * @return array
     */
    public function pay(int $price, string $resnumber, string $returnUrl): array
    {
        $response = Http::post('https://sep.shaparak.ir/onlinepg/onlinepg', [
            'action' => 'token',
            'TerminalId' => env('SEP_MERCHANT_ID'),
            'Amount' => $price,
            'ResNum' => $resnumber,
            'RedirectUrl' => $returnUrl,
            'CellNumber' => Auth::check() ? Auth::user()->mobile : '',
        ]);

        return ['token' => $response->json('token')];
    }

    /**
     * @param Payment $payment
     * @param array $info
     * @return bool
     */
    public function confirm(Payment $payment, array $info): bool
    {
        try {
            $payment->refnumber = $info['RefNum'];
            $payment->bank_name = 'Saman';
            $bankInfo = null;

            if($info['State'] === Payment::BANKSTATEOK)
            {
                $res = Http::post('https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction', [
                    'RefNum' => $info['RefNum'],
                    'TerminalNumber' => env('SEP_MERCHANT_ID'),
                ]);

                $resultCode = $res->json('ResultCode') ?? -5000;
                $bankInfo = json_encode(array_merge($info, ['verify_datail' => $res->json()]), true);

                $payment->bank_info = $bankInfo;

                if($resultCode === 2) {
                    return true;
                } elseif ($resultCode === 0) {
                    $payment->status = Payment::STATUSPAID;
                    $payment->sign = $payment->sign();
                    $payment->confirmed_by = 'system';

                    return DB::transaction(function () use ($payment) {
                        if ($payment->save()) {
                            $response = Wallet::increaseByBank($payment);
                            if ($response['status']) {
                                $payment->transaction_id = $response['transaction_id'];
                                $payment->save();
                                return true;
                            }
                        }
                        return false;
                    });
                }
            }

            $payment->status = Payment::STATUSREJECT;
            if ($info['State'] === 'CanceledByUser') {
                $payment->status = Payment::STATUSCANCELED;
            }
            if (is_null($bankInfo)) {
                $payment->bank_info = json_encode($info, true);
            }
            if ($payment->save()) {
//            $payment->user->notify(new SmsSystem(__('sms.paymentFailure'), 'force'));
//            $payment->user->notify(new MailSystem(__('email.onlinePurchaseFailed'), 'force'));
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    public function reject(Payment $payment): bool
    {
        if ($payment->status !== Payment::STATUSPAID) {
            return false;
        }

        try {
            $res = Http::post('https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction', [
                'RefNum' => $payment->refnumber,
                'TerminalNumber' => env('SEP_MERCHANT_ID'),
            ]);

            $resultCode = $res->json('ResultCode') ?? -5000;
            $bankInfo = json_encode(['verify_detail' => $res->json()], true);
        } catch (\Throwable $e) {
            Log::error('ReverseTransaction request failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        if($resultCode === 2) {
            return true;
        } elseif ($resultCode === 0) {
            $newPayment = new Payment();
            $newPayment->bank_info = $bankInfo;
            $newPayment->bank_name = 'saman';
            $newPayment->status = Payment::STATUSRETURNED;
            $newPayment->refnumber = $payment->refnumber;
            $newPayment->resnumber = time() . mt_rand(100000, 999999) . mt_rand(1000, 9999);
            $newPayment->price = $payment->price;
            $newPayment->type = $payment->type;
            $newPayment->return_url = $payment->return_url;
            $newPayment->user_id = $payment->user_id;
            $newPayment->confirmed_by = 'system';
            $newPayment->order_id = $payment->order_id;

            return DB::transaction(function () use ($newPayment, $payment) {
                if ($newPayment->save()) {
                    $newPayment->transaction_id = $payment->transaction_id;
                    $newPayment->sign = $newPayment->sign();
                    $newPayment->save();
                    return true;
                }
                return false;
            });
        }

        return false;
    }
}
