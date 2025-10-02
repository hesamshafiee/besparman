<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'payment.*']);
        Permission::create(['name' => 'payment.show']);
        Permission::create(['name' => 'payment.confirm']);
        Permission::create(['name' => 'payment.reject']);

    }
}
