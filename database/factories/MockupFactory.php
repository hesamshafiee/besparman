<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class MockupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id'   => Category::factory(),
            'name'          => $this->faker->words(2, true),
            'slug'          => $this->faker->unique()->slug(),
            'canvas_width'  => 2000,
            'canvas_height' => 2000,
            'dpi'           => 150,
            'print_x'       => 200,
            'print_y'       => 200,
            'print_width'   => 1600,
            'print_height'  => 1600,
            'print_rotation'=> 0,
            'fit_mode'      => 'contain',
            'layers'        => [
                'base'    => 'mockups/base/sample.png',
                'overlay' => null,
                'shadow'  => null,
                'mask'    => null,
            ],
            'preview_bg'    => '#FFFFFF',
            'is_active'     => true,
            'sort'          => 0,
        ];
    }
}
