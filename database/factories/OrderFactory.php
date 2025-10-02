<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'paid', 'cancelled', 'received', 'posted', 'preparation']),
            'price' => $this->faker->numberBetween(10000, 1000000),
            'final_price' => $this->faker->numberBetween(10000, 1000000),
            'total_discount' => $this->faker->numberBetween(0, 50000),
            'total_sale' => $this->faker->numberBetween(0, 50000),
            'store' => $this->faker->word(),
            'description' => $this->faker->sentence(),
        ];
    }
}
