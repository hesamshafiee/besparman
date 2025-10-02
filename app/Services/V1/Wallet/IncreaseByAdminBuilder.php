<?php

namespace App\Services\V1\Wallet;

use App\Models\WalletTransaction;

class IncreaseByAdminBuilder implements Builder
{

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $walletService = new WalletService(
            WalletTransaction::TYPE_INCREASE,
            WalletTransaction::DETAIL_INCREASE_ADMIN,
            WalletTransaction::STATUS_CONFIRMED,
            $data['user-id']
        );

        $walletService->description = $data['description'];
        $walletService->value = $data['value'];

        return $walletService->transaction();
    }
}
