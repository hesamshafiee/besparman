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
        Schema::create('group_charges', function (Blueprint $table) {
            $table->id();
            $table->string('group_type');
            $table->json('phone_numbers')->nullable();
            $table->json('phone_numbers_successful')->nullable();
            $table->json('phone_numbers_unsuccessful')->nullable();
            $table->json('topup_information');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_charges');
    }
};
