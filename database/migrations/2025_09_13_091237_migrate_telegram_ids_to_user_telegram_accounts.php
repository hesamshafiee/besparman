<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateTelegramIdsToUserTelegramAccounts extends Migration
{
    public function up()
    {
        $users = DB::table('users')->whereNotNull('telegram_id')->get();

        foreach ($users as $user) {
            DB::table('user_telegram_accounts')->insert([
                'user_id'     => $user->id,
                'telegram_id' => $user->telegram_id,
                'label'       => 'migrated',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('user_telegram_accounts')->where('label', 'migrated')->delete();
    }
}
