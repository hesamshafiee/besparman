<?php

namespace App\Services\V1\Wallet;

use App\Models\Payment;
use App\Models\WalletTransaction;

class IncreaseByCardBuilder implements Builder
{

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if ($data['status'] === Payment::STATUSUNPAID) {
            $wallet = new WalletService(
                WalletTransaction::TYPE_INCREASE,
                WalletTransaction::DETAIL_INCREASE_CARD,
                WalletTransaction::STATUS_CONFIRMED
            );

            $wallet->value = $data['value'];

            return $wallet->transaction();
        }

        return ['status' => false, 'error' => __('general.somethingWrong')];
    }
}
