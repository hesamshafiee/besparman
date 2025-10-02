<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProfitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'profit.*']);
        Permission::create(['name' => 'profit.show']);
        Permission::create(['name' => 'profit.create']);
        Permission::create(['name' => 'profit.update']);
        Permission::create(['name' => 'profit.delete']);
        Permission::create(['name' => 'profit.assignProfitGroup']);
    }
}
