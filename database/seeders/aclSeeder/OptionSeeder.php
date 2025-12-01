<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'option.*']);
        Permission::create(['name' => 'option.show']);
        Permission::create(['name' => 'option.create']);
        Permission::create(['name' => 'option.update']);
        Permission::create(['name' => 'option.delete']);
    }
}
