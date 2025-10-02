<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupCharge>
 */
class GroupChargeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_numbers' => '{"phones": ["0912000000","0912000000","0912000000","0912000000"]}',
            'phone_numbers_successful' => json_encode([]),
            'phone_numbers_unsuccessful' => json_encode([]),
            'status' => 0,
        ];
    }
}
