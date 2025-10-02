<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ScheduledTopupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'scheduled_topup.*']);
        Permission::create(['name' => 'scheduled_topup.show']);
        Permission::create(['name' => 'scheduled_topup.create']);
        Permission::create(['name' => 'scheduled_topup.update']);
        Permission::create(['name' => 'scheduled_topup.cancel']);
    }
}
