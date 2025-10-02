<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class IrancellOfferPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'irancell-offer-package.*']);
        Permission::create(['name' => 'irancell-offer-package.show']);
    }
}
