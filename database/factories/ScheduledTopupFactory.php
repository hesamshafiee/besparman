<?php

namespace Database\Factories;

use App\Models\ScheduledTopup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledTopupFactory extends Factory
{
    protected $model = ScheduledTopup::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending', // needed for cancel tests
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'payload' => json_encode([])
        ];
    }
}
