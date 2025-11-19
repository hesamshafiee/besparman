<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'name' => 'لباس',
                'children' => [
                    [
                        'name' => 'تیشرت',
                        'children' => [
                            ['name' => 'یقه گرد', 'base_price' => 6000000, 'markup_price' => 0, 'show_in_work' => 1],
                            ['name' => 'یقه هفت', 'base_price' => 5000000, 'markup_price' => 0, 'show_in_work' => 0],
                        ],
                    ],
                    [
                        'name' => 'هودی',
                        'children' => [
                            ['name' => 'ساده', 'base_price' => 7000000, 'markup_price' => 0, 'show_in_work' => 0],
                            ['name' => 'کلاهدار', 'base_price' => 5000000, 'markup_price' => 0, 'show_in_work' => 0],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'قاب',
                'children' => [
                    [
                        'name' => 'اپل',
                        'children' => [
                            ['name' => 'سخت', 'base_price' => 2000000, 'markup_price' => 0, 'show_in_work' => 1],
                            ['name' => 'نرم', 'base_price' => 2000000, 'markup_price' => 0, 'show_in_work' => 0],
                        ],
                    ],
                    [
                        'name' => 'سامسونگ',
                        'children' => [
                            ['name' => 'سخت', 'base_price' => 2000000, 'markup_price' => 0, 'show_in_work' => 0],
                            ['name' => 'نرم', 'base_price' => 2000000, 'markup_price' => 0, 'show_in_work' => 0],
                        ],
                    ],
                    [
                        'name' => 'شیاِومی',
                        'children' => [
                            ['name' => 'سخت', 'base_price' => 2000000, 'markup_price' => 0, 'show_in_work' => 0],
                            ['name' => 'نرم', 'base_price' => 2000000, 'markup_price' => 0, 'show_in_work' => 0],
                            ['name' => 'سه بعدی', 'base_price' => 2000000, 'markup_price' => 0, 'show_in_work' => 0],
                        ],
                    ],
                ],
            ],
        ];

        $this->seedCategories($categories, null, $now);
    }

    protected function seedCategories(array $items, ?int $parentId, Carbon $now): void
    {
        foreach ($items as $item) {
            $id = DB::table('categories')->insertGetId([
                'name'         => $item['name'],
                'base_price'   => $item['base_price']   ?? 0,
                'markup_price' => $item['markup_price'] ?? 0,
                'show_in_work' => $item['show_in_work'] ?? 0,
                'parent_id'    => $parentId,
                'data'         => json_encode([]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            if (!empty($item['children']) && is_array($item['children'])) {
                $this->seedCategories($item['children'], $id, $now);
            }
        }
    }
}
