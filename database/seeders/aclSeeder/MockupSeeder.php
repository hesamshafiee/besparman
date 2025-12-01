<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MockupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'mockup.*']);
        Permission::create(['name' => 'mockup.show']);
        Permission::create(['name' => 'mockup.create']);
        Permission::create(['name' => 'mockup.update']);
        Permission::create(['name' => 'mockup.delete']);
        Permission::create(['name' => 'mockup.image']);
    }
}
