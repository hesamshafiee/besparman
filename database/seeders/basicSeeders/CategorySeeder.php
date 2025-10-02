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
                    "name" => "همراه اول",
                    "data" => '[
      {
        "id": 1,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 2,
        "text": "بسته های اینترنت انارستان مخصوص سیم کارتهای اعتباری",
        "parent": 0
      },
      {
        "id": 3,
        "text": "بسته های مکالمه انارستان مخصوص سیم کارتهای اعتباری",
        "parent": 0
      },
      {
        "id": 4,
        "text": "روزانه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 6,
        "text": "2 ماهه",
        "parent": 0
      },
      {
        "id": 7,
        "text": "4 ماهه",
        "parent": 0
      },
      {
        "id": 8,
        "text": "بسته های پرفروش",
        "parent": 0
      }
    ]'
                ],
                [
                    "id" => 2,
                    "name" => "ایرانسل اعتباری",
                    "data" => '[
      {
        "id": 1,
        "text": "روزانه",
        "parent": 0
      },
      {
        "id": 2,
        "text": "3 روزه",
        "parent": 0
      },
      {
        "id": 3,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 4,
        "text": "15 روزه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 6,
        "text": "2 ماهه",
        "parent": 0
      },
      {
        "id": 7,
        "text": "4 ماهه",
        "parent": 0
      },
      {
        "id": 8,
        "text": "بسته های پر فروش",
        "parent": 0
      },
      {
        "id": 9,
        "text": "بسته مکالمه یکماهه",
        "parent": 0
      },
      {
        "id": 10,
        "text": "رومینگ ترکیه",
        "parent": 0
      },
      {
        "id": 11,
        "text": "رومینگ عراق",
        "parent": 0
      },
      {
        "id": 12,
        "text": "رومینگ کشورهای حوزه خلیج فارس",
        "parent": 0
      },
      {
        "id": 13,
        "text": "\tرومینگ چین - تایلند",
        "parent": 0
      }
    ]'
                ],
                [
                    "id" => 3,
                    "name" => "ایرانسل دائمی",
                    "data" => '[
      {
        "id": 1,
        "text": "روزانه",
        "parent": 0
      },
      {
        "id": 2,
        "text": "3 روزه",
        "parent": 0
      },
      {
        "id": 3,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 4,
        "text": "15 روزه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 6,
        "text": "2 ماهه",
        "parent": 0
      },
      {
        "id": 7,
        "text": "4 ماهه",
        "parent": 0
      },
      {
        "id": 8,
        "text": "بسته های پر فروش",
        "parent": 0
      },
      {
        "id": 9,
        "text": "بسته مکالمه یکماهه",
        "parent": 0
      },
      {
        "id": 10,
        "text": "رومینگ ترکیه",
        "parent": 0
      },
      {
        "id": 11,
        "text": "رومینگ عراق",
        "parent": 0
      },
      {
        "id": 12,
        "text": "رومینگ کشورهای حوزه خلیج فارس",
        "parent": 0
      },
      {
        "id": 13,
        "text": "\tرومینگ چین - تایلند",
        "parent": 0
      }
    ]'
                ],
                [
                    "id" => 4,
                    "name" => "ایرانسل TD-LTE",
                    "data" => '[
      {
        "id": 1,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 2,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 3,
        "text": "3 ماهه",
        "parent": 0
      },
      {
        "id": 4,
        "text": "6 ماهه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "یکساله",
        "parent": 0
      },
      {
        "id": 6,
        "text": "نامحدود",
        "parent": 0
      }
    ]'
                ],
                [
                    "id" => 5,
                    "name" => "آپتل",
                    "data" => '[
      {
        "id": 1,
        "text": "روزانه",
        "parent": 0
      },
      {
        "id": 2,
        "text": "3 روزه",
        "parent": 0
      },
      {
        "id": 3,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 4,
        "text": "15 روزه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 6,
        "text": "3 ماهه",
        "parent": 0
      },
      {
        "id": 7,
        "text": "6 ماهه",
        "parent": 0
      },
      {
        "id": 8,
        "text": "یکساله",
        "parent": 0
      },
      {
        "id": 9,
        "text": "رومینگ عراق 30 روزه",
        "parent": 0
      },
      {
        "id": 10,
        "text": "رومینگ ترکیه 30 روزه",
        "parent": 0
      },
      {
        "id": 11,
        "text": "رومینگ امارات 30 روزه",
        "parent": 0
      }
    ]'
                ],
                [
                    "id" => 6,
                    "name" => "اعتباری",
                    "data" => '[
      {
        "id": 1,
        "text": "بسته های خانواده دکا",
        "parent": 0
      },
      {
        "id": 2,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 3,
        "text": "15 روزه",
        "parent": 0
      },
      {
        "id": 4,
        "text": "روزانه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "3 روزه",
        "parent": 0
      },
      {
        "id": 6,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 7,
        "text": "حجم افزایشی",
        "parent": 0
      },
      {
        "id": 8,
        "text": "2 ماهه",
        "parent": 0
      },
      {
        "id": 9,
        "text": "3 ماهه",
        "parent": 0
      },
      {
        "id": 10,
        "text": "بسته مکالمه",
        "parent": 0
      },
      {
        "id": 11,
        "text": "رومینگ عراق",
        "parent": 0
      }
    ]'
                ],
                [
                    "id" => 7,
                    "name" => "دایمی",
                    "data" => '[
      {
        "id": 1,
        "text": "بسته های خانواده دکا",
        "parent": 0
      },
      {
        "id": 2,
        "text": "3 روزه",
        "parent": 0
      },
      {
        "id": 3,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 4,
        "text": "روزانه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 6,
        "text": "15 روزه",
        "parent": 0
      },
      {
        "id": 7,
        "text": "حجم افزایشی",
        "parent": 0
      },
      {
        "id": 8,
        "text": "2 ماهه",
        "parent": 0
      },
      {
        "id": 9,
        "text": "3 ماهه",
        "parent": 0
      },
      {
        "id": 10,
        "text": "بسته مکالمه",
        "parent": 0
      },
      {
        "id": 11,
        "text": "رومینگ عراق",
        "parent": 0
      }
    ]'
                ],
                [
                    "id" => 8,
                    "name" => "شاتل",
                    "data" => '[
      {
        "id": 1,
        "text": "روزانه",
        "parent": 0
      },
      {
        "id": 2,
        "text": "3 روزه",
        "parent": 0
      },
      {
        "id": 3,
        "text": "هفتگی",
        "parent": 0
      },
      {
        "id": 4,
        "text": "15 روزه",
        "parent": 0
      },
      {
        "id": 5,
        "text": "ماهانه",
        "parent": 0
      },
      {
        "id": 6,
        "text": "2 ماهه",
        "parent": 0
      },
      {
        "id": 7,
        "text": "3 ماهه",
        "parent": 0
      },
      {
        "id": 8,
        "text": "6 ماهه",
        "parent": 0
      },
      {
        "id": 9,
        "text": "بسته های پرفروش",
        "parent": 0
      },
      {
        "id": 10,
        "text": "بسته های ترکیبی شاتل موبایل+ ADSL شاتل",
        "parent": 0
      }
    ]'
    ],
    [
      "id" => 9,
      "name" => "دايمی آپتل",
      "data" => '[
{
"id": 1,
"text": "روزانه",
"parent": 0
},
{
"id": 2,
"text": "3 روزه",
"parent": 0
},
{
"id": 3,
"text": "هفتگی",
"parent": 0
},
{
"id": 4,
"text": "15 روزه",
"parent": 0
},
{
"id": 5,
"text": "ماهانه",
"parent": 0
},
{
"id": 6,
"text": "3 ماهه",
"parent": 0
},
{
"id": 7,
"text": "6 ماهه",
"parent": 0
},
{
"id": 8,
"text": "یکساله",
"parent": 0
},
{
"id": 9,
"text": "رومینگ عراق 30 روزه",
"parent": 0
},
{
"id": 10,
"text": "رومینگ ترکیه 30 روزه",
"parent": 0
},
{
"id": 11,
"text": "رومینگ امارات 30 روزه",
"parent": 0
}
]'
  ]
            ]
        );
    }
}
