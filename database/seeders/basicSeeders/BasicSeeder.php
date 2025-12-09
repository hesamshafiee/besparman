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
            UserSeeder::class,
            CategorySeeder::class,
            OptionSeeder::class,
            OptionValueSeeder::class,
            CategoryOptionSeeder::class,
            MockupSeeder::class,
            ProfitGroupSeeder::class

            //ProductSeeder::class,
            //ProfitSeeder::class
        ]);
    }
}
