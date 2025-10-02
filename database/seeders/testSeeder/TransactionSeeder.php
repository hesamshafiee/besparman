<?php

namespace Database\Seeders\testSeeder;

use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('wallets')->insert(
            [
                ['user_id' => 1, 'value' => mt_rand(1000, 1000000) . '0000']
            ]
        );
        WalletTransaction::factory(100)->create();
    }
}
