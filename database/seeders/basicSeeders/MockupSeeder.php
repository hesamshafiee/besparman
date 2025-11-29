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
        /**
         * برای هر مسیر دسته (قاب / برند / نوع) یک واریانت و یک موکاپ می‌سازیم.
         * مسیرها باید با CategorySeeder فعلی هم‌خوان باشند.
         */
        $configs = [
            [
                'category_path' => ['قاب', 'اپل', 'سخت'],
                'variant_sku'   => 'CASE-APPLE-HARD',
                'name'          => 'موکاپ قاب اپل (سخت)',
                'slug'          => 'case-apple-hard',
                'sort'          => 10,
                'folder'        => 'cases/apple/hard',
            ],
            [
                'category_path' => ['قاب', 'اپل', 'نرم'],
                'variant_sku'   => 'CASE-APPLE-SOFT',
                'name'          => 'موکاپ قاب اپل (نرم)',
                'slug'          => 'case-apple-soft',
                'sort'          => 20,
                'folder'        => 'cases/apple/soft',
            ],
            [
                'category_path' => ['قاب', 'سامسونگ', 'سخت'],
                'variant_sku'   => 'CASE-SAMSUNG-HARD',
                'name'          => 'موکاپ قاب سامسونگ (سخت)',
                'slug'          => 'case-samsung-hard',
                'sort'          => 30,
                'folder'        => 'cases/samsung/hard',
            ],
            [
                'category_path' => ['قاب', 'سامسونگ', 'نرم'],
                'variant_sku'   => 'CASE-SAMSUNG-SOFT',
                'name'          => 'موکاپ قاب سامسونگ (نرم)',
                'slug'          => 'case-samsung-soft',
                'sort'          => 40,
                'folder'        => 'cases/samsung/soft',
            ],
            [
                'category_path' => ['قاب', 'شیاِومی', 'سخت'],
                'variant_sku'   => 'CASE-XIAOMI-HARD',
                'name'          => 'موکاپ قاب شیاِومی (سخت)',
                'slug'          => 'case-xiaomi-hard',
                'sort'          => 50,
                'folder'        => 'cases/xiaomi/hard',
            ],
            [
                'category_path' => ['قاب', 'شیاِومی', 'سه بعدی'],
                'variant_sku'   => 'CASE-XIAOMI-3D',
                'name'          => 'موکاپ قاب شیاِومی (سه‌بعدی)',
                'slug'          => 'case-xiaomi-3d',
                'sort'          => 60,
                'folder'        => 'cases/xiaomi/3d',
            ],
        ];

        foreach ($configs as $cfg) {
            $leafCategoryId = $this->ensureCategoryPath($cfg['category_path']);

            // واریانت مرتبط با این دسته
            $variant = Variant::firstOrCreate(
                [
                    'category_id' => $leafCategoryId,
                    'sku'         => $cfg['variant_sku'],
                ],
                [
                    'stock'     => 0,
                    'add_price' => 0,
                    'is_active' => true,
                ]
            );

            $variantId = (int) $variant->id;

            // تعریف فیلدهای موکاپ
            $row = [
                'name'           => $cfg['name'],
                'slug'           => $cfg['slug'], // اگر خالی بود پایین دوباره تولید می‌کنیم
                'canvas_width'   => 3000,
                'canvas_height'  => 6000,
                'dpi'            => 300,
                'print_x'        => 300,
                'print_y'        => 600,
                'print_width'    => 2400,
                'print_height'   => 4800,
                'print_rotation' => 0,
                'fit_mode'       => 'contain',
                'layers'         => [
                    'base'    => "/storage/mockups/{$cfg['folder']}/base.png",
                    'overlay' => "/storage/mockups/{$cfg['folder']}/overlay.png",
                    'shadow'  => "/storage/mockups/{$cfg['folder']}/shadow.png",
                    'mask'    => "/storage/mockups/{$cfg['folder']}/mask.png",
                ],
                'preview_bg'     => '#FFFFFF',
                'is_active'      => 1,
                'sort'           => $cfg['sort'],
                'variant_id'     => $variantId, // 👈 کلیدی که گفتی
            ];

            $slug = $row['slug'] ?: Str::slug($row['name']);

            // تا با هر بار seed تکراری نشه
            Mockup::updateOrCreate(
                ['slug' => $slug],
                $row + ['slug' => $slug]
            );
        }
    }

    /**
     * مثل نسخه‌ی قبلی خودت:
     * مسیر ['قاب','اپل','سخت'] رو طی می‌کنه و categoryها رو
     * بر اساس name + parent_id firstOrCreate می‌کند.
     * با CategorySeeder فعلی هم سازگار است چون اگر قبلاً ساخته شده باشند،
     * همونا رو برمی‌گردونه.
     */
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
                    // اگر ستون slug داری اینجا هم می‌تونی ست کنی:
                    // 'slug' => $slug,
                ]
            );

            $parentId = $cat->id;
        }

        return (int) $parentId;
    }
}
