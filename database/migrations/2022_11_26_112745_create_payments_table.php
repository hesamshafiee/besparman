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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('wallet_transactions');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->BigInteger('price');
            $table->string('resnumber');
            $table->string('refnumber')->nullable();
            $table->enum('type', ['online', 'card']);
            $table->string('confirmed_by')->nullable();
            $table->string('status' );
            $table->string('bank_name')->nullable();
            $table->mediumText('bank_info')->nullable();
            $table->string('return_url', 300)->nullable();
            $table->boolean('used')->default(0);
            $table->string('sign')->nullable();
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
        Schema::dropIfExists('payments');
    }
};
