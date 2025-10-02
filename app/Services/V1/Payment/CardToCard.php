<?php

namespace App\Services\V1\Payment;

use App\Models\Payment;
use App\Notifications\V1\MailSystem;
use App\Notifications\V1\SmsSystem;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CardToCard implements Gateway
{
    /**
     * @param int $price
     * @param string $resNumber
     * @param string $returnUrl
     * @return array
     */
    public function pay(int $price, string $resNumber, string $returnUrl): array
    {
        return [];
    }

    /**
     * @param Payment $payment
     * @param $info
     * @return bool
     */
    public function confirm(Payment $payment, $info): bool
    {
        if ($payment->status === Payment::STATUSUNPAID) {
            return DB::transaction(function () use ($payment) {
                $response = Wallet::cardToCard($payment);

                if ($response['status']) {
                    $payment->status = Payment::STATUSPAID;
                    $payment->confirmed_by = Auth::user()->confirmedBy();
                    $payment->sign = $payment->sign();
                    $payment->transaction_id = $response['transaction_id'];
                    $payment->save();
                    return true;
                } else {
                    return false;
                }
            });
        }

        return false;
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    public function reject(Payment $payment): bool
    {
        if ($payment->type === $payment::TYPE_CARD) {
            $payment->status = Payment::STATUSREJECT;
            $payment->confirmed_by = Auth::user()->confirmedBy();
            $payment->sign = $payment->sign();

            if ($payment->save()) {
//                $payment->user->notify(new SmsSystem(__('sms.paymentFailure'), 'force'));
//                $payment->user->notify(new MailSystem(__('email.cardToCardFailed'), 'force'));
                return true;
            }
        }

        return false;
    }
}
