<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prize_purchases', function (Blueprint $table) {
            $table->json('price')->nullable()->after('third_party_info');
            $table->json('points')->nullable()->after('third_party_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prize_purchases', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('points');
        });
    }
};
