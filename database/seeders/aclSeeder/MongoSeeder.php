<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MongoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'mongo.*']);
        Permission::create(['name' => 'mongo.report_daily_balances']);
        Permission::create(['name' => 'mongo.report_daily_users']);
    }
}
