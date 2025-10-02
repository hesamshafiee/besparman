<?php

namespace App\Services\V1\Wallet;

use App\Models\WalletTransaction;

class IncreaseByRefundBuilder implements Builder
{

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $transaction = $data['transaction'];
        $newTransaction = $transaction->replicate();
        $newTransaction->profit = bcsub($newTransaction->profit, $transaction->profit, 4);
        $newTransaction->type = WalletTransaction::TYPE_INCREASE;
        $newTransaction->detail = WalletTransaction::DETAIL_INCREASE_REFUND;


        $walletService = new WalletService(
            WalletTransaction::TYPE_INCREASE,
            WalletTransaction::DETAIL_INCREASE_REFUND,
            WalletTransaction::STATUS_CONFIRMED,
            $transaction->user_id,
            $transaction->order_id,
            $transaction->extra_info
        );

        $walletService->value = $transaction->value;
        $walletService->profit = empty($transaction->profit) ? $transaction->profit : -$transaction->profit;
        $walletService->operatorId = $transaction->operator_id;
        $walletService->userType = $transaction->user_type;
        $walletService->province = $transaction->province;
        $walletService->city = $transaction->city;
        $walletService->mainPage = $transaction->main_page;
        $walletService->productType = $transaction->product_type;
        $walletService->productName = $transaction->product_name;

        $walletService = $walletService->transaction();

        if ($walletService['status']) {
            return ['status' => true];
        }

        return ['status' => false];
    }
}
