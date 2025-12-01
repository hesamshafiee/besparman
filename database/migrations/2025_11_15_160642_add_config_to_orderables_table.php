<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*Schema::table('orderables', function (Blueprint $table) {
            // snapshot از محصول در لحظه‌ی سفارش
            $table->json('product_snapshot')->nullable()->after('price');

            // config آیتم: settings / mockup / preview / options / meta
            $table->json('config')->nullable()->after('product_snapshot');
        });*/
    }

    public function down(): void
    {
        /*Schema::table('orderables', function (Blueprint $table) {
            $table->dropColumn('product_snapshot');
            $table->dropColumn('config');
        });*/
    }
};
