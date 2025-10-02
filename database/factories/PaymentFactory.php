<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Menu>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => 1,
            'price' => mt_rand(1, 1000000) . '.0000',
            'resnumber' => time() . mt_rand(100000, 999999) . mt_rand(1000, 9999),
            'type' => Payment::TYPE_ONLINE,
            'status' => $this->faker->randomElement([
                Payment::STATUSPAID,
                Payment::STATUSCANCELED,
                Payment::STATUSUNPAID,
                Payment::STATUSREJECT,
                Payment::STATUSRETURNED
            ]),
            'used' => $this->faker->randomElement([0, 1]),
        ];
    }
}
