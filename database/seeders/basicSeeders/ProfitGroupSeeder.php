<?php

namespace Database\Seeders\basicSeeders;

use App\Models\ProfitGroup;
use Illuminate\Database\Seeder;

class ProfitGroupSeeder extends Seeder
{
    public function run(): void
    {
        ProfitGroup::updateOrCreate(
            [
                'title' => 'تعرفه اصلی',
            ],
            [
                'designer_profit' => 40, 
                'site_profit'     => 60,
                'referrer_profit' => 0,
            ]
        );
    }
}
