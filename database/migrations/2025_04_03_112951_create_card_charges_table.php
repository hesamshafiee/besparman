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
        Schema::create('card_charges', function (Blueprint $table) {
            $table->id();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->unsignedBigInteger('order_id')->nullable()->default(null);
            $table->unsignedBigInteger('operator_id')->nullable()->default(null);
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('no action');
            $table->string('serial')->unique();
            $table->string('pin') ;
            $table->unsignedBigInteger('price');
            $table->float('profit');
            $table->string('file_name');
            $table->string('status')->default('open');
            $table->integer('file_status')->default(0);
            $table->unsignedBigInteger('user_id')->nullable()->default(null);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->unsignedBigInteger('product_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('no action');
            $table->timestamp('saled_at')->default(null)->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_charges');
    }
};
