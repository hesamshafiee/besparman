<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsernameAndNameToUserTelegramAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('user_telegram_accounts', function (Blueprint $table) {
            $table->string('username')->nullable()->after('telegram_id');
            $table->string('name')->nullable()->after('username');
        });
    }

    public function down()
    {
        Schema::table('user_telegram_accounts', function (Blueprint $table) {
            $table->dropColumn(['username', 'name']);
        });
    }
}
