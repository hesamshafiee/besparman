<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class BasicSeeder extends Seeder
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
            OperatorSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductPhysicalCardChargeSeeder::class,
            ProfitSeeder::class
        ]);
    }
}
