<?php

namespace App\Services\V1\Wallet;

use App\Models\Wallet as WalletModel;
use App\Models\WalletTransaction;
use App\Notifications\V1\MailSystem;
use App\Notifications\V1\SmsSystem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfirmTransferBuilder implements Builder
{
    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $response = ['status' => false, 'error' => __('general.somethingWrong')];
        $transactionId = $data['transaction-id'];
        $walletTransaction = WalletTransaction::findOrFail($transactionId);
        $fromWallet = WalletModel::where('user_id', $walletTransaction->transfer_from_id)->firstOrFail();

        if($fromWallet->value < $walletTransaction->value) {
            return $response;
        }

        return DB::transaction(function () use ($walletTransaction, $fromWallet, $response) {
            $walletTransaction->status = WalletTransaction::STATUS_CONFIRMED;
            $walletTransaction->confirmed_by = Auth::user()->confirmedBy();
            $walletTransaction->sign = $walletTransaction->sign();

            if ($walletTransaction->save()) {
                $fromWallet->value -= $walletTransaction->value;

                if ($fromWallet->save()) {
                    $walletService = new WalletService(
                        WalletTransaction::TYPE_INCREASE,
                        WalletTransaction::DETAIL_INCREASE_TRANSFER,
                        WalletTransaction::STATUS_CONFIRMED,
                        $walletTransaction->transfer_to_id
                    );

                    $walletService->value = $walletTransaction->value;
                    $walletService->transferFromId = $walletTransaction->transfer_from_id;
                    $walletService->transferToId = $walletTransaction->transfer_to_id;
                    $response = $walletService->transaction();

                    $user = $walletTransaction->transferFrom;
//                    $user->notify(new SmsSystem(__('sms.confirmTransfer'), 'force'));
//                    $user->notify(new MailSystem(__('email.confirmTransfer'), 'force', __('email.confirmTransferSubject')));
                }
            }
            return $response;
        });
    }
}
