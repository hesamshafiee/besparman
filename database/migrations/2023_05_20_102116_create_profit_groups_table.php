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
        Schema::create('profit_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');

            // new columns
            $table->decimal('designer_profit', 5, 2);
            $table->decimal('site_profit', 5, 2);
            $table->decimal('referrer_profit', 5, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_groups');
    }
};
