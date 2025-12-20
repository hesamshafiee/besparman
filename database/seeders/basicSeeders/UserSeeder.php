<?php

namespace Database\Seeders\basicSeeders;

use App\Jobs\SuperAdminJob;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

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
                'name' => 'picboom',
                'mobile' => User::MOBILE_ADMIN,
                'mobile_verified_at' => now(),
                'presenter_code' => Str::random(7),
                'type' => User::TYPE_PICBOOM,
                'password' => null,
            ],
            [
                'name' => 'تست',
                'mobile' => config('app.mobile_number_test_1'),
                'mobile_verified_at' => now(),
                'presenter_code' => Str::random(7),
                'type' => User::TYPE_ADMIN,
                'password' => null,
            ],
            [
                'name' => 'hesam shafiee',
                'mobile' => config('app.mobile_number_test_3'),
                'mobile_verified_at' => now(),
                'presenter_code' => Str::random(7),
                'type' => User::TYPE_PANEL,
                'password' => '$2y$10$n0b6cJVcAldzsle7lkT.n.mAYebXLnPbdE9GWR6jHB8I.5LKEIOGu', // 09124345864hH!
              
                
            ],
            
        ]);

        SuperAdminJob::dispatch();
    }
}
