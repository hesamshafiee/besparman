<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\Prize;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class PrizeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'price' => 1000,
            'point' => 100,
            'type' => Prize::TYPE_CELL_DIRECT_CHARGE,
            'operator_id' => 1,
        ];
    }
}
