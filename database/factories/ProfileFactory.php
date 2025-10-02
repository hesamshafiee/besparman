<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'province' => 'تهران',
            'city' => 'تهران',
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->date,
            'store_name' => fake()->title,
            'address' => 'تست',
            'postal_code' => fake()->postcode,
            'national_code' => time() . Str::random(10),
            'gender' => 'female',
        ];
    }
}
