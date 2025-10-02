<?php

namespace App\Services\V1\Wallet;

use App\Models\Payment;
use App\Models\WalletTransaction;

class IncreaseByBankBuilder implements Builder
{

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if ($data['status'] === Payment::STATUSPAID) {
            $wallet = new WalletService(
                WalletTransaction::TYPE_INCREASE,
                WalletTransaction::DETAIL_INCREASE_ONLINE,
                WalletTransaction::STATUS_CONFIRMED,
                $data['userId'],
                $data['orderId']
            );


            $wallet->value = $data['value'];
            $wallet->mainPage = !empty($data['orderId']);

            return $wallet->transaction();
        }

        return ['status' => false, 'error' => __('general.somethingWrong')];
    }
}
