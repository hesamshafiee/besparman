<?php

namespace Database\Seeders\testSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->call([
            PaymentSeeder::class,
            TransactionSeeder::class
        ]);
    }
}
