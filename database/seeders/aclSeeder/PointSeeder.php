<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'point.*']);
        Permission::create(['name' => 'point.show']);
        Permission::create(['name' => 'point.create']);
        Permission::create(['name' => 'point.update']);
        Permission::create(['name' => 'point.delete']);
    }
}
