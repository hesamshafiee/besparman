<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'tag.*']);
        Permission::create(['name' => 'tag.show']);
        Permission::create(['name' => 'tag.create']);
        Permission::create(['name' => 'tag.update']);
        Permission::create(['name' => 'tag.delete']);


    }
}
