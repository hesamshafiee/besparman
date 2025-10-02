<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'discount.*']);
        Permission::create(['name' => 'discount.show']);
        Permission::create(['name' => 'discount.create']);
        Permission::create(['name' => 'discount.update']);
        Permission::create(['name' => 'discount.delete']);
        Permission::create(['name' => 'discount.image']);
    }
}
