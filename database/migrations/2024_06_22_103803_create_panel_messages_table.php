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
        Schema::create('panel_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('short_content');
            $table->text('body');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_open')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panel_messages');
    }
};
