<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Menu>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'wallet_id' => 1,
            'wallet_value_after_transaction' => mt_rand(1, 1000000) . '.0000',
            'type' => $this->faker->randomElement([
                WalletTransaction::TYPE_INCREASE,
                WalletTransaction::TYPE_DECREASE
            ]),
            'resnumber' => time() . mt_rand(100000, 999999) . mt_rand(1000, 9999),
            'value' => mt_rand(1, 1000000) . '.0000',
            'status' => $this->faker->randomElement([
                WalletTransaction::STATUS_CONFIRMED,
                WalletTransaction::STATUS_PENDING,
                WalletTransaction::STATUS_REJECTED
            ]),
            'detail' => $this->faker->randomElement([
                WalletTransaction::DETAIL_INCREASE_REFUND,
                WalletTransaction::DETAIL_DECREASE_ONLINE,
                WalletTransaction::DETAIL_DECREASE_ADMIN,
                WalletTransaction::DETAIL_DECREASE_PURCHASE,
                WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER,
                WalletTransaction::DETAIL_DECREASE_TRANSFER,
                WalletTransaction::DETAIL_INCREASE_PURCHASE_ESAJ,
                WalletTransaction::DETAIL_INCREASE_TRANSFER,
                WalletTransaction::DETAIL_INCREASE_PURCHASE_PRESENTER,
            ]),
            'user_id' => 1,
        ];
    }
}
