<?php

namespace Database\Seeders\basicSeeders;

use App\Jobs\SuperAdminJob;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'esaj',
                'mobile' => User::MOBILE_ESAJ,
                'mobile_verified_at' => now(),
                'presenter_code' => Str::random(7),
                'type' => User::TYPE_ESAJ,
            ],
            [
                'name' => 'تست',
                'mobile' => config('app.mobile_number_test_1'),
                'mobile_verified_at' => now(),
                'presenter_code' => Str::random(7),
                'type' => User::TYPE_ADMIN,
            ]
        ]);

        SuperAdminJob::dispatch();
    }
}
