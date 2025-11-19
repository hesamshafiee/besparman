<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Category;
use App\Models\Mockup;
use App\Models\Variant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MockupSeeder extends Seeder
{
    public function run(): void
    {
        $leafCategoryId = $this->ensureCategoryPath([
            'محصولات',
            'تی‌شرت‌ها',
            'مردانه سفید',
        ]);

        $variant = Variant::firstOrCreate(
            [
                'category_id' => $leafCategoryId,
                'sku'         => 'TSHIRT-MEN-WHITE',
            ],
            [
                'stock'     => 0,
                'add_price' => 0,
                'is_active' => true,
            ]
        );

        $variantId = (int) $variant->id;

        $items = [
            [
                'name'           => 'تی‌شرت مردانه سفید',
                'slug'           => 'tshirt-men-white',
                'canvas_width'   => 4500,
                'canvas_height'  => 5400,
                'dpi'            => 300,
                'print_x'        => 1200,
                'print_y'        => 900,
                'print_width'    => 2100,
                'print_height'   => 3000,
                'print_rotation' => 0,
                'fit_mode'       => 'contain',
                'layers' => [
                    'base'    => '/storage/mockups/tshirt_men_white/base.png',
                    'overlay' => '/storage/mockups/tshirt_men_white/overlay.png',
                    'shadow'  => '/storage/mockups/tshirt_men_white/shadow.png',
                    'mask'    => '/storage/mockups/tshirt_men_white/mask.png',
                ],
                'preview_bg'     => '#FFFFFF',
                'is_active'      => 1,
                'sort'           => 1,
                'variant_id'     => $variantId,   // 👈 دیگه category_id نیست
            ],
        ];

        foreach ($items as $row) {
            $slug = $row['slug'] ?? Str::slug($row['name']);

            Mockup::updateOrCreate(
                ['slug' => $slug],
                $row + ['slug' => $slug]
            );
        }
    }


    protected function ensureCategoryPath(array $parts): int
    {
        $parentId = null;

        foreach ($parts as $name) {
            $slug = Str::slug($name, '-');

            $cat = Category::firstOrCreate(
                [
                    'name'      => $name,
                    'parent_id' => $parentId,
                ],
                [
                    'data' => [],
                    // اگر ستون slug داری، این‌جا هم می‌تونی ست کنی:
                    // 'slug' => $slug,
                ]
            );

            $parentId = $cat->id;
        }

        return (int) $parentId;
    }
}
