<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class WalletTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'walletTransaction.*']);
        Permission::create(['name' => 'walletTransaction.show']);
        Permission::create(['name' => 'walletTransaction.increaseByAdmin']);
        Permission::create(['name' => 'walletTransaction.decreaseByAdmin']);
        Permission::create(['name' => 'walletTransaction.confirmTransfer']);
        Permission::create(['name' => 'walletTransaction.rejectTransfer']);
        Permission::create(['name' => 'walletTransaction.cardToCard']);
        Permission::create(['name' => 'walletTransaction.update']);

    }
}
