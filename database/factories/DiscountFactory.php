<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'code' => time() . Str::random(),
            'type' => $this->faker->randomElement(['money' , 'percent']),
            'value' => mt_rand(10, 50),
            'status' => $this->faker->randomElement([0 , 1]),
        ];
    }
}
