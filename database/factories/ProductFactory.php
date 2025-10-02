<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => time() . Str::random(),
            'description' => time() . Str::random(),
            'sku' => time() . Str::random(),
            'price' => mt_rand(100000, 10000000),
            'type' => $this->faker->randomElement([
                Product::TYPE_CELL_INTERNET_PACKAGE,
                Product::TYPE_TD_LTE_INTERNET_PACKAGE,
                Product::TYPE_CELL_DIRECT_CHARGE,
                Product::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                Product::TYPE_CELL_AMAZING_DIRECT_CHARGE,
                Product::TYPE_CART
            ]),
            'status' => $this->faker->randomElement([0 , 1]),
        ];
    }
}
