<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoryOptionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $colorId      = DB::table('options')->where('code', 'color')->value('id');
        $sizeId       = DB::table('options')->where('code', 'size')->value('id');
        $materialId   = DB::table('options')->where('code', 'material')->value('id');
        $phoneModelId = DB::table('options')->where('code', 'phone_model')->value('id');

        // دسته‌ها
        $tshirtId  = DB::table('categories')->where('name', 'تیشرت')->value('id');
        $hoodieId  = DB::table('categories')->where('name', 'هودی')->value('id');
        $appleId   = DB::table('categories')->where('name', 'اپل')->value('id');
        $samsungId = DB::table('categories')->where('name', 'سامسونگ')->value('id');
        $xiaomiId  = DB::table('categories')->where('name', 'شیاِومی')->value('id');

        $rows = [
            // تیشرت: رنگ + سایز + جنس
            [$tshirtId, $colorId],
            [$tshirtId, $sizeId],
            [$tshirtId, $materialId],

            // هودی: رنگ + سایز
            [$hoodieId, $colorId],
            [$hoodieId, $sizeId],

            // قاب‌ها: رنگ + مدل گوشی
            [$appleId,   $colorId],
            [$appleId,   $phoneModelId],

            [$samsungId, $colorId],
            [$samsungId, $phoneModelId],

            [$xiaomiId,  $colorId],
            [$xiaomiId,  $phoneModelId],
        ];

        foreach ($rows as [$categoryId, $optionId]) {
            if ($categoryId && $optionId) {
                DB::table('category_option')->insert([
                    'category_id' => $categoryId,
                    'option_id'   => $optionId,
                    'is_required' => null, // یعنی از خود options.is_required تبعیت کند
                    'is_active'   => 1,
                    'sort_order'  => 0,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }
}
