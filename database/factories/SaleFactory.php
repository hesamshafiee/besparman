<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Sale::class;

    public function definition()
    {
        $date_end = now()->addMonth();
        $date_start = fake()->date('Y-m-d', $date_end);
        return [
            'title' => fake()->title(),
            'type'  => 'percent',
            'value' => 25,
            'start_date' => $date_start,
            'end_date'   => $date_end,
            'status' => fake()->boolean(90)
        ];
    }
}
