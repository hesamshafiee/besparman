<?php

namespace Database\Factories;

use App\Models\Logistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logistic>
 */
class LogisticFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'city' => fake()->city(),
            'province' => fake()->city(),
            'country' => fake()->country(),
            'type' => $this->faker->randomElement(),
            'capacity' => $this->faker->randomElement(['10' , '20', '30']),
            'price' => $this->faker->randomElement(['1000' , '2000', '3000']),
            'start_time' => 8,
            'end_time' => 18,
            'divide_time' => 2,
            'is_active_in_holiday' => $this->faker->randomElement([0 , 1]),
            'days_not_working' => '{"monday" : {}}',
        ];
    }
}
