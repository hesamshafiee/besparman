<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Database\Seeder;

class OptionValueSeeder extends Seeder
{
    public function run(): void
    {
        $color    = Option::where('code', 'color')->first();
        $size     = Option::where('code', 'size')->first();
        $material = Option::where('code', 'material')->first();

        if ($color) {
            OptionValue::firstOrCreate(
                ['code' => 'red'],
                ['option_id' => $color->id, 'name' => 'Red', 'meta' => ['hex' => '#FF0000'], 'is_active' => 1, 'sort_order' => 0]
            );

            OptionValue::firstOrCreate(
                ['code' => 'blue'],
                ['option_id' => $color->id, 'name' => 'Blue', 'meta' => ['hex' => '#0000FF'], 'is_active' => 1, 'sort_order' => 1]
            );
        }

        if ($size) {
            OptionValue::firstOrCreate(
                ['code' => 'size-s'],
                ['option_id' => $size->id, 'name' => 'S', 'meta' => ['price_modifier' => 0], 'is_active' => 1, 'sort_order' => 0]
            );

            OptionValue::firstOrCreate(
                ['code' => 'size-m'],
                ['option_id' => $size->id, 'name' => 'M', 'meta' => ['price_modifier' => 5000], 'is_active' => 1, 'sort_order' => 1]
            );
        }

        if ($material) {
            OptionValue::firstOrCreate(
                ['code' => 'cotton'],
                ['option_id' => $material->id, 'name' => 'Cotton', 'meta' => ['weight_modifier' => 50], 'is_active' => 1, 'sort_order' => 0]
            );
        }
    }
}
