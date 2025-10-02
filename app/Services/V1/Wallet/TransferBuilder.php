<?php

namespace App\Services\V1\Wallet;

use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;

class TransferBuilder implements Builder
{
    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $walletService = new WalletService(
            WalletTransaction::TYPE_DECREASE,
            WalletTransaction::DETAIL_DECREASE_TRANSFER,
            WalletTransaction::STATUS_PENDING
        );

        $walletService->transferToId = $data['transferToId'];
        $walletService->transferFromId = Auth::id();
        $walletService->value = $data['value'];

        return $walletService->transaction();
    }
}
