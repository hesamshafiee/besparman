<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'address.*']);
        Permission::create(['name' => 'address.show']);
        Permission::create(['name' => 'address.create']);
        Permission::create(['name' => 'address.update']);
        Permission::create(['name' => 'address.delete']);
    }
}
