<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'setting.*']);
        Permission::create(['name' => 'setting.show']);
        Permission::create(['name' => 'setting.create']);
        Permission::create(['name' => 'setting.update']);
        Permission::create(['name' => 'setting.delete']);


    }
}
