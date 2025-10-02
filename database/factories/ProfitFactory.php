<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Profit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProfitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'operator_id' => 1,
            'type' => $this->faker->randomElement([
                Profit::TYPE_CELL_INTERNET_PACKAGE,
                Profit::TYPE_CELL_DIRECT_CHARGE,
                Profit::TYPE_CELL_CARD_CHARGE
            ]),
            'title' => $this->faker->name,
            'status' => fake()->boolean(90),
            'profit' => mt_rand(10, 100),
        ];
    }
}
