<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wallet_transaction_extras', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('mobile')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->string('type')->nullable()->change();
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->string('operator_title')->nullable()->change();
            $table->boolean('third_party_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transaction_extras', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->string('mobile')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->string('operator_title')->nullable(false)->change();
            $table->boolean('third_party_status')->nullable(false)->change();
        });
    }
};
