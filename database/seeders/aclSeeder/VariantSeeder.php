<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class VariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'variant.*']);
        Permission::create(['name' => 'variant.show']);
        Permission::create(['name' => 'variant.create']);
        Permission::create(['name' => 'variant.update']);
        Permission::create(['name' => 'variant.delete']);
    }
}
