<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'profile.*']);
        Permission::create(['name' => 'profile.show']);
        Permission::create(['name' => 'profile.create']);
        Permission::create(['name' => 'profile.update']);
        Permission::create(['name' => 'profile.delete']);
    }
}
