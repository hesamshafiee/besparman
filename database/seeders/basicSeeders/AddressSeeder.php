<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Address;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;


class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('mobile', config('app.mobile_number_test_3'))->first();

         if (! $user) {
            $this->command->error('User mobile_number_test_3 not found.');
            return;
        }
        $works = Address::factory()
            ->count(3)
            ->create([
                'user_id' => $user->id,
            ]);

    }
}
