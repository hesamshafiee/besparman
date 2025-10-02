<?php

namespace App\Services\V1\Wallet;

use App\Models\WalletTransaction;
use App\Notifications\V1\MailSystem;
use App\Notifications\V1\SmsSystem;
use Illuminate\Support\Facades\Auth;

class RejectTransferBuilder implements Builder
{
    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $transactionId = $data['transaction-id'];
        $rejectMessage = $data['message'];
        $walletTransaction = WalletTransaction::findOrFail($transactionId);
        $walletTransaction->status = WalletTransaction::STATUS_REJECTED;
        $walletTransaction->confirmed_by = Auth::user()->confirmedBy();
        $walletTransaction->sign = $walletTransaction->sign();

        $walletTransaction->description = $rejectMessage;
        if ($walletTransaction->save()) {
            $user = $walletTransaction->transferFrom;
//            $user->notify(new SmsSystem(__('sms.transferFailed'), 'force'));
//            $user->notify(new MailSystem(__('email.transferFailed'), 'force', __('email.transferFailedSubject')));
            return ['status' => true];
        }

        return ['status' => false, 'error' => __('general.somethingWrong')];
    }
}
