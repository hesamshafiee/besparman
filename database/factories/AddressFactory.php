<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'title'       => $this->faker->randomElement(['Home', 'Office']),
            'province'    => $this->faker->state,
            'city'        => $this->faker->city,
            'address'     => $this->faker->streetAddress,
            'postal_code' => $this->faker->postcode,
            'phone'       => $this->faker->phoneNumber,
            'mobile'      => $this->faker->phoneNumber,
            'is_default'  => $this->faker->boolean(20),
        ];
    }
}
