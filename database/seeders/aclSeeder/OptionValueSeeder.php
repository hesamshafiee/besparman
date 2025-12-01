<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OptionValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'option-value.*']);
        Permission::create(['name' => 'option-value.show']);
        Permission::create(['name' => 'option-value.create']);
        Permission::create(['name' => 'option-value.update']);
        Permission::create(['name' => 'option-value.delete']);
    }
}
