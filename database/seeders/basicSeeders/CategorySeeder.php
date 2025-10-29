<?php

namespace Database\Seeders\basicSeeders;

use App\Jobs\SuperAdminJob;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('categories')->insert(
            [
                [
                    "id" => 1,
                    "name" => "محصولات",
                    "data" => '[
      {
        "id": 1,
        "text": "پوشاک با چاپ استاندارد",
        "parent": 0
      },
      {
        "id": 2,
        "text": "پوشاک با چاپ بزرگ",
        "parent": 0
      },
      {
        "id": 3,
        "text": "کلاه‌ها",
        "parent": 0
      },
      {
        "id": 4,
        "text": "تی‌شرت‌های گرافیکی",
        "parent": 0
      },
      {
        "id": 5,
        "text": "لباس‌های خط A (دامن گشاد از کمر)",
        "parent": 0
      },
      {
        "id": 6,
        "text": "برچسب‌ها و آهنرباها",
        "parent": 0
      },
      {
        "id": 7,
        "text": "قاب گوشی",
        "parent": 0
      },
      {
        "id": 8,
        "text": "پد میز",
        "parent": 0
      },
      {
        "id": 9,
        "text": "پد ماوس",
        "parent": 0
      },
      {
        "id": 10,
        "text": "بالش‌ها و ساک‌های دستی",
        "parent": 0
      },
      {
        "id": 11,
        "text": "چاپ‌ها، کارت‌ها و پوسترها",
        "parent": 0
      },
      {
        "id": 12,
        "text": "کیف‌های کوچک، روکش و کاور لپ‌تاپ",
        "parent": 0
      },
      {
        "id": 13,
        "text": "روتختی‌ها، پتوهای نرم و پرده‌های حمام",
        "parent": 0
      },
      {
        "id": 14,
        "text": "ماگ‌ها",
        "parent": 0
      },
      {
        "id": 15,
        "text": "شال‌ها",
        "parent": 0
      },
      {
        "id": 16,
        "text": "کیف‌های بندی",
        "parent": 0
      }
      ,{
        "id": 17,
        "text": "دفترچه‌های سیمی",
        "parent": 0
      }
      ,{
        "id": 18,
        "text": "دفترهای جلدسخت",
        "parent": 0
      }
      ,{
        "id": 19,
        "text": "ساعت‌ها",
        "parent": 0
      }
      ,{
        "id": 20,
        "text": "چاپ‌های تخته هنری",
        "parent": 0
      }
      ,{
        "id": 21,
        "text": "بلوک‌های اکریلیک و زیرلیوانی‌ها",
        "parent": 0
      }
      ,{
        "id": 22,
        "text": "پتوهای تزئینی و پارچه‌های دیواری",
        "parent": 0
      },
      {
        "id": 23,
        "text": "پادری‌های حمام",
        "parent": 0
      },
      {
        "id": 24,
        "text": "ساک‌های دستی کتانی",
        "parent": 0
      },
      {
        "id": 25,
        "text": "سنجاق‌ها",
        "parent": 0
      },
      {
        "id": 26,
        "text": "پازل‌ها",
        "parent": 0
      },
      {
        "id": 27,
        "text": "جوراب‌ها",
        "parent": 0
      }
    ]'
                ],
                
                
            ]
        );
    }
}
