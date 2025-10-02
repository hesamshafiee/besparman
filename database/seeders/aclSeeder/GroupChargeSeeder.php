<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class GroupChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'group-charge.*']);
        Permission::create(['name' => 'group-charge.show']);
        Permission::create(['name' => 'group-charge.create']);
        Permission::create(['name' => 'group-charge.cancel']);
    }
}
