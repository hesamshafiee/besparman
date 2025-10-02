<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Permission::create(['name' => 'sale.*']);
        Permission::create(['name' => 'sale.show']);
        Permission::create(['name' => 'sale.create']);
        Permission::create(['name' => 'sale.update']);
        Permission::create(['name' => 'sale.delete']);

    }
}
