<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Logistic;


class LogesticSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Logistic::factory()->count(1)->create(['city' => 'تهران', 'province' => 'تهران', 'country' => 'ایران',
            'type' => 'پست', 'capacity' => '200', 'price' => '500000',
            'start_time' => 8, 'end_time' => 18, 'divide_time' => 2,
            'is_active_in_holiday' => 1,
            'days_not_working' => '{"monday" : {}}',
        ]);
    }
}
