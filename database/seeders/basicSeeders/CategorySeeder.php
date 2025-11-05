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

        // 1️⃣ اول ریشه را درج می‌کنیم
        $parentId = DB::table('categories')->insertGetId([
            'name' => 'محصولات',
            'parent_id' => null,
            'data' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2️⃣ حالا زیرمجموعه‌ها را درج می‌کنیم
        $children = [
            'پوشاک با چاپ استاندارد',
            'پوشاک با چاپ بزرگ',
            'کلاه‌ها',
            'تی‌شرت‌های گرافیکی',
            'لباس‌های خط A (دامن گشاد از کمر)',
            'برچسب‌ها و آهنرباها',
            'قاب گوشی',
            'پد میز',
            'پد ماوس',
            'بالش‌ها و ساک‌های دستی',
            'چاپ‌ها، کارت‌ها و پوسترها',
            'کیف‌های کوچک، روکش و کاور لپ‌تاپ',
            'روتختی‌ها، پتوهای نرم و پرده‌های حمام',
            'ماگ‌ها',
            'شال‌ها',
            'کیف‌های بندی',
            'دفترچه‌های سیمی',
            'دفترهای جلدسخت',
            'ساعت‌ها',
            'چاپ‌های تخته هنری',
            'بلوک‌های اکریلیک و زیرلیوانی‌ها',
            'پتوهای تزئینی و پارچه‌های دیواری',
            'پادری‌های حمام',
            'ساک‌های دستی کتانی',
            'سنجاق‌ها',
            'پازل‌ها',
            'جوراب‌ها',
        ];

        $data = [];
        foreach ($children as $name) {
            $data[] = [
                'name' => $name,
                'parent_id' => $parentId,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('categories')->insert($data);
    }
}
