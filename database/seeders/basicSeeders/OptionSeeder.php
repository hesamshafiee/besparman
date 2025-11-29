<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OptionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $options = [
            /*[
                'name' => 'رنگ',
                'code' => 'color',
                'type' => 'color',
                'display_type' => 'color-picker',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'سایز',
                'code' => 'size',
                'type' => 'select',
                'display_type' => 'select',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'جنس',
                'code' => 'material',
                'type' => 'select',
                'display_type' => 'select',
                'is_required' => false,
                'sort_order' => 3,
            ],*/
            [
                'name' => 'مدل گوشی',
                'code' => 'phone_model',
                'type' => 'select',
                'display_type' => 'select',
                'is_required' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($options as $opt) {
            DB::table('options')->insert([
                'name'         => $opt['name'],
                'code'         => $opt['code'],
                'type'         => $opt['type'],
                'display_type' => $opt['display_type'],
                'is_required'  => $opt['is_required'],
                'is_active'    => 1,
                'meta'         => json_encode([]),
                'sort_order'   => $opt['sort_order'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
