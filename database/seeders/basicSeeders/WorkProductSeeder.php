<?php

namespace Database\Seeders\basicSeeders;

use App\Models\User;
use App\Models\Work;
use App\Models\Product;
use Illuminate\Database\Seeder;

class WorkProductSeeder extends Seeder
{
    public function run(): void
    {
        // Find the specific user
        $user = User::where('mobile', config('app.mobile_number_test_3'))->first();

        if (! $user) {
            $this->command->error('User mobile_number_test_3 not found.');
            return;
        }

        // Create works for this user
        $works = Work::factory()
            ->count(3)
            ->create([
                'user_id' => $user->id,
            ]);

        // Create products for each work
        $works->each(function ($work) use ($user) {
            Product::factory()
                ->count(5)
                ->create([
                    'user_id' => $user->id,
                    'work_id' => $work->id,
                ]);
        });
    }
}
