<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class WorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'work.*']);
        Permission::create(['name' => 'work.show']);
        Permission::create(['name' => 'work.create']);
        Permission::create(['name' => 'work.update']);
        Permission::create(['name' => 'work.delete']);
    }
}
