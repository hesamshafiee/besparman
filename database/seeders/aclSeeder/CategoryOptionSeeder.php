<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CategoryOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'category-option.*']);
        Permission::create(['name' => 'category-option.show']);
        Permission::create(['name' => 'category-option.create']);
        Permission::create(['name' => 'category-option.update']);
        Permission::create(['name' => 'category-option.delete']);
    }
}
