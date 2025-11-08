<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Category;
use App\Models\Mockup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MockupSeeder extends Seeder
{
    public function run(): void
    {
        // 1) تضمین وجود کتگوری هدف و گرفتن id برگ (leaf)
        // مثلا مسیر: محصولات → تی‌شرت‌ها → مردانه سفید
        $leafCategoryId = $this->ensureCategoryPath([
            'محصولات',
            'تی‌شرت‌ها',
            'مردانه سفید',
        ]);

        // 2) دیتای موکاپ‌ها (layers را آرایه بده؛ مدل خودکار cast می‌کند)
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
                'category_id'    => $leafCategoryId,
            ],
            // ... هر موکاپ دیگری هم همینجا اضافه کن، فقط category_id را همین $leafCategoryId بده
        ];

        // 3) insert امن/idempotent
        foreach ($items as $row) {
            $slug = $row['slug'] ?? Str::slug($row['name']);
            Mockup::updateOrCreate(
                ['slug' => $slug],
                $row + ['slug' => $slug]
            );
        }
    }

    /**
     * مسیر کتگوری را تضمین می‌کند و id برگ را برمی‌گرداند.
     * مثلا ['محصولات','تی‌شرت‌ها','مردانه سفید']
     */
    protected function ensureCategoryPath(array $parts): int
    {
        $parentId = null;

        foreach ($parts as $name) {
            $slug = Str::slug($name, '-');

            /** @var \App\Models\Category $cat */
            $cat = Category::firstOrCreate(
                ['name' => $name, 'parent_id' => $parentId],
                [
                    'data'      => [],
                    // اگر ستون slug داری، اینو هم ست کن:
                    // 'slug'   => $slug,
                ]
            );

            $parentId = $cat->id;
        }

        return (int) $parentId;
    }
}
