<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OptionValueSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $colorId      = DB::table('options')->where('code', 'color')->value('id');
        $sizeId       = DB::table('options')->where('code', 'size')->value('id');
        $materialId   = DB::table('options')->where('code', 'material')->value('id');
        $phoneModelId = DB::table('options')->where('code', 'phone_model')->value('id');

        $values = [
            // رنگ‌ها
            [
                'option_id' => $colorId,
                'name'      => 'قرمز',
                'code'      => 'red',
                'meta'      => json_encode(['color' => '#FF0000']),
            ],
            [
                'option_id' => $colorId,
                'name'      => 'آبی',
                'code'      => 'blue',
                'meta'      => json_encode(['color' => '#0000FF']),
            ],
            [
                'option_id' => $colorId,
                'name'      => 'مشکی',
                'code'      => 'black',
                'meta'      => json_encode(['color' => '#000000']),
            ],
            [
                'option_id' => $colorId,
                'name'      => 'سفید',
                'code'      => 'white',
                'meta'      => json_encode(['color' => '#FFFFFF']),
            ],

            // سایزها
            ['option_id' => $sizeId, 'name' => 'S',  'code' => 's'],
            ['option_id' => $sizeId, 'name' => 'M',  'code' => 'm'],
            ['option_id' => $sizeId, 'name' => 'L',  'code' => 'l'],
            ['option_id' => $sizeId, 'name' => 'XL', 'code' => 'xl'],

            // جنس
            ['option_id' => $materialId, 'name' => 'نخی',       'code' => 'cotton'],
            ['option_id' => $materialId, 'name' => 'پلی‌استر',  'code' => 'polyester'],

            // مدل‌های گوشی – APPLE
            ['option_id' => $phoneModelId, 'name' => 'iPhone 11',       'code' => 'iphone_11'],
            ['option_id' => $phoneModelId, 'name' => 'iPhone 12',       'code' => 'iphone_12'],
            ['option_id' => $phoneModelId, 'name' => 'iPhone 13',       'code' => 'iphone_13'],
            ['option_id' => $phoneModelId, 'name' => 'iPhone 14',       'code' => 'iphone_14'],

            // SAMSUNG
            ['option_id' => $phoneModelId, 'name' => 'Galaxy S21',      'code' => 's21'],
            ['option_id' => $phoneModelId, 'name' => 'Galaxy S22',      'code' => 's22'],
            ['option_id' => $phoneModelId, 'name' => 'Galaxy S23',      'code' => 's23'],

            // XIAOMI
            ['option_id' => $phoneModelId, 'name' => 'Poco X5',         'code' => 'poco_x5'],
            ['option_id' => $phoneModelId, 'name' => 'Redmi Note 10',   'code' => 'note_10'],
            ['option_id' => $phoneModelId, 'name' => 'Redmi Note 11',   'code' => 'note_11'],
        ];

        foreach ($values as $v) {
            DB::table('option_values')->insert([
                'option_id'   => $v['option_id'],
                'name'        => $v['name'],
                'code'        => $v['code'],
                'meta'        => $v['meta'] ?? json_encode([]),
                'is_active'   => 1,
                'sort_order'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }
}
