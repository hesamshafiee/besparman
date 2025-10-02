<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'menu.*']);
        Permission::create(['name' => 'menu.show']);
        Permission::create(['name' => 'menu.create']);
        Permission::create(['name' => 'menu.update']);
        Permission::create(['name' => 'menu.delete']);


    }
}
