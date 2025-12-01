<?php

namespace Database\Factories;

use App\Models\Work;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkFactory extends Factory
{
    protected $model = Work::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(3);

        return [
            'user_id'      => User::factory(), // اگر کاربر از قبل داری، تست می‌فرسته
            'title'        => $title,
            'slug'         => Str::slug($title) . '-' . Str::random(4),
            'description'  => $this->faker->paragraph(),
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
