<?php

namespace Database\Seeders\aclSeeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'comment.*']);
        Permission::create(['name' => 'comment.show']);
        Permission::create(['name' => 'comment.create']);
        Permission::create(['name' => 'comment.update']);
        Permission::create(['name' => 'comment.delete']);


    }
}
