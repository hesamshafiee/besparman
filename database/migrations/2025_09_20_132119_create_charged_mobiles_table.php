<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('charged_mobiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->string('mobile', 32);
            $table->primary(['user_id', 'mobile']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('charged_mobiles');
    }
};
