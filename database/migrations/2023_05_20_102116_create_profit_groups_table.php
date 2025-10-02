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
            $table->json('profit_split_ids');
            $table->timestamps();
        });

        Schema::create('profit_groupables', function (Blueprint $table) {
            $table->unsignedInteger('profit_group_id');
            $table->unsignedInteger('profit_groupable_id');
            $table->string('profit_groupable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_groupables');
        Schema::dropIfExists('profit_groups');
    }
};
