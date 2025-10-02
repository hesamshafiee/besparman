<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'warehouse.*']);
        Permission::create(['name' => 'warehouse.show']);
        Permission::create(['name' => 'warehouse.create']);
        Permission::create(['name' => 'warehouse.update']);
        Permission::create(['name' => 'warehouse.delete']);
    }
}
