<?php

namespace App\Services\V1\Wallet;

use App\Models\Payment;
use App\Models\WalletTransaction;

class DecreaseByBankBuilder implements Builder
{
    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $payment = $data['payment'];

        $walletService = new WalletService(
            WalletTransaction::TYPE_DECREASE,
            WalletTransaction::DETAIL_DECREASE_ONLINE,
            WalletTransaction::STATUS_CONFIRMED,
            $payment->user_id,
            $payment->order_id
        );

        $walletService->value = $payment->price;
        $walletService->mainPage = true;


        return $walletService->transaction();
    }
}
