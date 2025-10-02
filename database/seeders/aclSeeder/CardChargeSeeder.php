<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CardChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'card-charge.*']);
        Permission::create(['name' => 'card-charge.show']);
        Permission::create(['name' => 'card-charge.create']);
        Permission::create(['name' => 'card-charge.suspension']);
        Permission::create(['name' => 'card-charge.findBySerial']);
        Permission::create(['name' => 'card-charge.freeReport']);
    }
}
