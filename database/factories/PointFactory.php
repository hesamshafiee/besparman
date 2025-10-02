<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class PointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'value' => 1000,
            'point' => 1,
            'type' => Point::TYPE_CELL_DIRECT_CHARGE,
            'operator_id' => 1,
        ];
    }
}
