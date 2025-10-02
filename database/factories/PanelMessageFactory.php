<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PanelMessage>
 */
class PanelMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->name,
            'short_content' => $this->faker->title,
            'short_content' => $this->faker->text,
            'status' => 1,
            'is_open' => 1,
        ];
    }
}
