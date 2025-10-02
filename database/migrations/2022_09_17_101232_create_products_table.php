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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('description')->nullable();
            $table->text('description_full')->nullable();
            $table->string('sku', 100)->unique()->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('second_price')->nullable();
            $table->unsignedBigInteger('showable_price')->nullable();
            $table->string('type');
            $table->unsignedSmallInteger('minimum_sale')->nullable();
            $table->string('dimension', 50)->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->json('options')->nullable();
            $table->tinyInteger('deliverable')->default(0);
            $table->string('third_party_id')->nullable();
            $table->unsignedSmallInteger('period')->nullable();
            $table->string('sim_card_type')->nullable();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('no action');
            $table->string('profile_id')->nullable();
            $table->boolean('private')->default(0);
            $table->unsignedInteger('order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
