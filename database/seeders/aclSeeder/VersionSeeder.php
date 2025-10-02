<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'version.*']);
        Permission::create(['name' => 'version.show']);
        Permission::create(['name' => 'version.create']);
        Permission::create(['name' => 'version.update']);
        Permission::create(['name' => 'version.delete']);
    }
}
