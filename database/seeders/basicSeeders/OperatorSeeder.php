<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('operators')->insert([
            [
                'name' => 'Mci',
                'title' => 'MCI',
                'status' => 1,
                'id' => 1,
                'setting' => '{
                  "credit_cell_internet": 1,
                  "credit_td_lte_internet": 1,
                  "credit_cell_direct_charge": 1,
                  "credit_cell_amazing_direct_charge": 1,
                  "credit_cell_internet_direct_charge": 1,
                  "permanent_cell_internet": 1,
                  "permanent_td_lte_internet": 1,
                  "permanent_cell_direct_charge": 1,
                  "permanent_cell_internet_direct_charge": 1,
                  "radin_status": 0,
                  "radin_limit": 0,
                  "igap_limit": 0,
                  "radin_limit_package": 0,
                  "igap_limit_package": 0
                }'
            ],
            [
                'name' => 'Irancell',
                'title' => 'IRANCELL',
                'status' => 1,
                'id' => 2,
                'setting' => '{
                  "credit_cell_internet": 1,
                  "credit_td_lte_internet": 1,
                  "credit_cell_direct_charge": 1,
                  "credit_cell_amazing_direct_charge": 1,
                  "credit_cell_internet_direct_charge": 1,
                  "permanent_cell_internet": 1,
                  "permanent_td_lte_internet": 1,
                  "permanent_cell_direct_charge": 1,
                  "permanent_cell_internet_direct_charge": 1
                }'
            ],
            [
                'name' => 'Rightel',
                'title' => 'RIGHTEL',
                'status' => 1,
                'id' => 3,
                'setting' => '{
                  "credit_cell_internet": 1,
                  "credit_td_lte_internet": 1,
                  "credit_cell_direct_charge": 1,
                  "credit_cell_amazing_direct_charge": 1,
                  "credit_cell_internet_direct_charge": 1,
                  "permanent_cell_internet": 1,
                  "permanent_td_lte_internet": 1,
                  "permanent_cell_direct_charge": 1,
                  "permanent_cell_internet_direct_charge": 1
                }'
            ],
            [
                'name' => 'Aptel',
                'title' => 'APTEL',
                'status' => 1,
                'id' => 4,
                'setting' => '{
                  "credit_cell_internet": 1,
                  "credit_td_lte_internet": 1,
                  "credit_cell_direct_charge": 1,
                  "credit_cell_amazing_direct_charge": 1,
                  "credit_cell_internet_direct_charge": 1,
                  "permanent_cell_internet": 1,
                  "permanent_td_lte_internet": 1,
                  "permanent_cell_direct_charge": 1,
                  "permanent_cell_internet_direct_charge": 1
                }'
            ],
            [
                'name' => 'Shatel',
                'title' => 'SHATEL',
                'status' => 1,
                'id' => 5,
                'setting' => '{
                  "credit_cell_internet": 1,
                  "credit_td_lte_internet": 1,
                  "credit_cell_direct_charge": 1,
                  "credit_cell_amazing_direct_charge": 1,
                  "credit_cell_internet_direct_charge": 1,
                  "permanent_cell_internet": 1,
                  "permanent_td_lte_internet": 1,
                  "permanent_cell_direct_charge": 1,
                  "permanent_cell_internet_direct_charge": 1
                }'
            ],
        ]);
    }
}
