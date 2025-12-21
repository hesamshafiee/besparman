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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('no action');
            $table->decimal('wallet_value_after_transaction', 17, 4);
            $table->enum('type', ['increase', 'decrease']);
            $table->string('resnumber');
            $table->string('refnumber')->nullable();
            $table->decimal('value', 17, 4);
            $table->bigInteger('profit')->nullable();
            $table->enum('status' , ['pending', 'confirmed', 'rejected']);
            $table->unsignedBigInteger('transfer_from_id')->nullable();
            $table->foreign('transfer_from_id')->references('id')->on('users')->onDelete('no action');
            $table->unsignedBigInteger('transfer_to_id')->nullable();
            $table->foreign('transfer_to_id')->references('id')->on('users')->onDelete('no action');
            $table->string('confirmed_by')->nullable();
            $table->string('description')->nullable();
            $table->string('sign')->nullable();
            $table->string('detail');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('no action');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->json('extra_info')->nullable();
            $table->json('third_party_info')->nullable();
            $table->boolean('third_party_status')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('user_type')->nullable();
            $table->string('product_type')->nullable();
            $table->string('webservice_code')->unique()->nullable();
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
        Schema::dropIfExists('wallet_transactions');
    }
};
