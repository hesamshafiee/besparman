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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['money', 'percent']);
            $table->decimal('value', $precision = 10, $scale = 2);
            $table->tinyInteger('reusable')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamp('expire_at')->nullable()->default(null);
            $table->timestamps();
        });

        Schema::create('discountables', function (Blueprint $table) {
            $table->unsignedInteger('discount_id');
            $table->unsignedInteger('discountable_id');
            $table->string('discountable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discountables');
        Schema::dropIfExists('discounts');
    }
};
