<?php

namespace Database\Factories;

use App\Models\Operator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operator>
 */
class OperatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'title' => $this->faker->title,
            'status' => fake()->boolean(90),
            'setting' => '{
                  "credit_cell_internet": 0,
                  "credit_td_lte_internet": 0,
                  "credit_cell_direct_charge": 0,
                  "credit_cell_amazing_direct_charge": 0,
                  "credit_cell_internet_direct_charge": 0,
                  "permanent_cell_internet": 0,
                  "permanent_td_lte_internet": 0,
                  "permanent_cell_direct_charge": 0,
                  "permanent_cell_internet_direct_charge": 0
                }'
        ];
    }
}
