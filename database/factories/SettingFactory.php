<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'settings' => json_encode([
                'sms' => 1,
                'email' => null,
                'auth' => 'otp',
                'otp' => 'sms',
                'jwt_expiration_time' => null
            ]),
        ];
    }
}
