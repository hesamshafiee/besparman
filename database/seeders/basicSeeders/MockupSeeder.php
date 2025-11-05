<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Mockup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MockupSeeder extends Seeder
{
    public function run(): void
    {
        // مثال: برای دسته "تی‌شرت‌های گرافیکی" که id=4 فرض شده
        $categoryId = 4;

        $items = [
            [
                'name' => 'تی‌شرت مردانه سفید',
                'slug' => Str::slug('تی‌شرت مردانه سفید') . '-men-white',
                'canvas_width'  => 4500,
                'canvas_height' => 5400,
                'dpi'           => 300,
                'print_x'       => 1200,
                'print_y'       => 900,
                'print_width'   => 2100,
                'print_height'  => 3000,
                'print_rotation'=> 0,
                'fit_mode'      => 'contain',
                'layers'        => [
                    'base'    => '/storage/mockups/tshirt_men_white/base.png',
                    'overlay' => '/storage/mockups/tshirt_men_white/overlay.png',
                    'shadow'  => '/storage/mockups/tshirt_men_white/shadow.png',
                    'mask'    => '/storage/mockups/tshirt_men_white/mask.png',
                ],
                'preview_bg'    => '#FFFFFF',
                'is_active'     => true,
                'sort'          => 1,
            ],
            [
                'name' => 'تی‌شرت زنانه مشکی',
                'slug' => Str::slug('تی‌شرت زنانه مشکی') . '-women-black',
                'canvas_width'  => 4500,
                'canvas_height' => 5400,
                'dpi'           => 300,
                'print_x'       => 1150,
                'print_y'       => 850,
                'print_width'   => 2200,
                'print_height'  => 3100,
                'print_rotation'=> 0,
                'fit_mode'      => 'contain',
                'layers'        => [
                    'base'    => '/storage/mockups/tshirt_women_black/base.png',
                    'overlay' => '/storage/mockups/tshirt_women_black/overlay.png',
                    'shadow'  => '/storage/mockups/tshirt_women_black/shadow.png',
                    'mask'    => '/storage/mockups/tshirt_women_black/mask.png',
                ],
                'preview_bg'    => '#000000',
                'is_active'     => true,
                'sort'          => 2,
            ],
        ];

        foreach ($items as $it) {
            Mockup::updateOrCreate(
                ['slug' => $it['slug']],
                $it + ['category_id' => $categoryId]
            );
        }
    }
}
