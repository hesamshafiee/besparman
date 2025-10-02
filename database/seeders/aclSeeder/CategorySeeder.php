<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'category.*']);
        Permission::create(['name' => 'category.show']);
        Permission::create(['name' => 'category.create']);
        Permission::create(['name' => 'category.update']);
        Permission::create(['name' => 'category.delete']);
        Permission::create(['name' => 'category.image']);
    }
}
