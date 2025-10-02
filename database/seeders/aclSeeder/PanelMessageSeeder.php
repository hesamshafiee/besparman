<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PanelMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'panel-message.*']);
        Permission::create(['name' => 'panel-message.show']);
        Permission::create(['name' => 'panel-message.create']);
        Permission::create(['name' => 'panel-message.update']);
        Permission::create(['name' => 'panel-message.delete']);
    }
}
