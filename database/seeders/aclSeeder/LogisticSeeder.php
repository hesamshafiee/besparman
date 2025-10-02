<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class LogisticSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'logistic.*']);
        Permission::create(['name' => 'logistic.show']);
        Permission::create(['name' => 'logistic.create']);
        Permission::create(['name' => 'logistic.update']);
        Permission::create(['name' => 'logistic.delete']);
    }
}
