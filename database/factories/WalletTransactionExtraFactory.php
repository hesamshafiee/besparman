<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class WalletTransactionExtraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'taken_value' => 10000,
            'value' => 10000,
            'name' => fake()->name,
            'type' => fake()->name,
            'sim_card_type' => fake()->name,
            'product_id' => 1,
            'operator_title' => fake()->name,
            'operator_id' => 1,
            'third_party_status' => 1
        ];
    }
}
