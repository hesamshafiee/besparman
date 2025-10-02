<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LandingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'landing.*']);
        Permission::create(['name' => 'landing.show']);
        Permission::create(['name' => 'landing.create']);
        Permission::create(['name' => 'landing.update']);
        Permission::create(['name' => 'landing.delete']);
    }
}
