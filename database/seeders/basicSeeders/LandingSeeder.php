<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LandingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('landings')->insert([
            [
                'title' => 'irancell',
                'content' => '{}'
            ],
            [
                'title' => 'hamrah',
                'content' => '{}'
            ],
            [
                'title' => 'rightel',
                'content' => '{}'
            ],
            [
                'title' => 'aptel',
                'content' => '{}'
            ],
            [
                'title' => 'shatel-mobile',
                'content' => '{}'
            ],
            [
                'title' => 'irancell-charge',
                'content' => '{}'
            ],
            [
                'title' => 'hamrah-charge',
                'content' => '{}'
            ],
            [
                'title' => 'rightel-charge',
                'content' => '{}'
            ],
            [
                'title' => 'aptel-charge',
                'content' => '{}'
            ],
            [
                'title' => 'shatel-mobile-charge',
                'content' => '{}'
            ],
            [
                'title' => 'irancell-internet-package',
                'content' => '{}'
            ],
            [
                'title' => 'hamrah-internet-package',
                'content' => '{}'
            ],
            [
                'title' => 'rightel-internet-package',
                'content' => '{}'
            ],
            [
                'title' => 'aptel-internet-package',
                'content' => '{}'
            ],
            [
                'title' => 'shatel-mobile-internet-package',
                'content' => '{}'
            ],
            [
                'title' => 'bill-irancell',
                'content' => '{}'
            ],
            [
                'title' => 'panel-shop',
                'content' => '{}'
            ]
        ]);
    }
}
