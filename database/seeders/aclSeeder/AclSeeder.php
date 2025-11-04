<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AclSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->call([
            SuperAdminSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            DiscountSeeder::class,
            SaleSeeder::class,
            WalletTransactionSeeder::class,
            PaymentSeeder::class,
            MenuSeeder::class,
            LogSeeder::class,
            LogisticSeeder::class,
            DeliverySeeder::class,
            WarehouseSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            ProfitSeeder::class,
            ProfileSeeder::class,
            OperatorSeeder::class,
            LandingSeeder::class,
            CommentSeeder::class,
            PanelMessageSeeder::class,
            PointSeeder::class,
            PrizeSeeder::class,
            PointHistorySeeder::class,
            AddressSeeder::class,
            OrderSeeder::class,
            VersionSeeder::class,
            WorkSeeder::class
        ]);
    }
}
