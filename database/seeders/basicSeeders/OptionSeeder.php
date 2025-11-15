<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Option;
use Illuminate\Database\Seeder;

class OptionSeeder extends Seeder
{
    public function run(): void
    {
        Option::firstOrCreate([
            'code' => 'color',
        ], [
            'name'        => 'Color',
            'type'        => 'select',
            'is_required' => true,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 1,
        ]);

        Option::firstOrCreate([
            'code' => 'size',
        ], [
            'name'        => 'Size',
            'type'        => 'select',
            'is_required' => true,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 2,
        ]);

        Option::firstOrCreate([
            'code' => 'material',
        ], [
            'name'        => 'Material',
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 3,
        ]);
    }
}
