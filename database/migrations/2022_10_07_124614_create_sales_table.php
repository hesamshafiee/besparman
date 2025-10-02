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

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('title',128);
            $table->enum('type', ['money', 'percent']);
            $table->decimal('value', $precision = 10, $scale = 2);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });


        Schema::create('saleables', function (Blueprint $table) {
            $table->unsignedInteger('sale_id');
            $table->unsignedInteger('saleable_id');
            $table->string('saleable_type');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saleables');
        Schema::dropIfExists('sales');
    }
};
