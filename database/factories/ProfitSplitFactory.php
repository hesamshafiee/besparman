<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProfitSplitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'profit_id' => 1,
            'title' => fake()->title,
            'presenter_profit' => mt_rand(1, 100),
            'seller_profit' => mt_rand(1, 100),
        ];
    }
}
