<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PrizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'prize.*']);
        Permission::create(['name' => 'prize.show']);
        Permission::create(['name' => 'prize.create']);
        Permission::create(['name' => 'prize.update']);
        Permission::create(['name' => 'prize.delete']);
    }
}
