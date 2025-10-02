<?php

namespace Database\Seeders;

use Database\Seeders\aclSeeder\AclSeeder;
use Database\Seeders\basicSeeders\BasicSeeder;
use Database\Seeders\basicSeeders\LandingSeeder;
use Database\Seeders\testSeeder\TestSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AclSeeder::class,
            BasicSeeder::class,
            LandingSeeder::class
        ]);

        if (env('APP_ENV') === 'local') {
            $this->call([
                TestSeeder::class
            ]);
        }
    }
}
