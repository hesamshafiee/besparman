<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logistics', function (Blueprint $table) {
            $table->id();
            $table->string('city',128);
            $table->string('province',128);
            $table->string('country',128);
            $table->string('type',128);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('min_price_for_free_delivery')->nullable();
            $table->unsignedTinyInteger('start_delivery_after_day')->nullable();
            $table->unsignedTinyInteger('start_delivery_after_time')->nullable();
            $table->unsignedTinyInteger('start_time')->nullable();
            $table->unsignedTinyInteger('end_time')->nullable();
            $table->unsignedTinyInteger('divide_time')->nullable();
            $table->boolean('is_active_in_holiday')->default(0);
            $table->json('days_not_working');
            $table->boolean('status')->default(0);
            $table->boolean('default')->default(0);
            $table->boolean('is_capital')->default(0);
            $table->string('description')->nullable();
            $table->unsignedInteger('order')->nullable();
            $table->timestamps();
            $table->unique(['city', 'province', 'country']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logistics');
    }
};
