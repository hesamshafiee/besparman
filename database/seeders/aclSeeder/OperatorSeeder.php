<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OperatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'operator.*']);
        Permission::create(['name' => 'operator.show']);
        Permission::create(['name' => 'operator.create']);
        Permission::create(['name' => 'operator.update']);
        Permission::create(['name' => 'operator.delete']);
    }
}
