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
        Schema::table('logistics', function (Blueprint $table) {
            $table->unsignedTinyInteger('start_time')->nullable()->change();
            $table->unsignedTinyInteger('end_time')->nullable()->change();
            $table->unsignedTinyInteger('divide_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics', function (Blueprint $table) {
            $table->unsignedTinyInteger('start_time')->nullable(false)->change();
            $table->unsignedTinyInteger('end_time')->nullable(false)->change();
            $table->unsignedTinyInteger('divide_time')->nullable(false)->change();
        });
    }
};
