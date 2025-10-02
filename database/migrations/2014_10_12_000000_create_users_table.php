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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->date('birth_date')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('national_code')->nullable();
            $table->string('postal_code', 50)->nullable();
            $table->string('profession')->nullable();
            $table->string('education')->nullable();
            $table->string('store_name')->nullable();
            $table->string('province');
            $table->string('city');
            $table->enum('gender', ['female', 'male'])->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('ips')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('no action');
            $table->string('name')->nullable();
            $table->string('mobile')->unique();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('google2fa')->nullable();
            $table->boolean('two_step')->default(0);
            $table->string('presenter_code', 7)->unique();
            $table->timestamp('profile_confirm')->nullable();
            $table->enum('type', ['admin', 'panel', 'webservice', 'ordinary', 'esaj'])->default('ordinary');
            $table->unsignedBigInteger('points')->default(0);
            $table->boolean('private')->default(0);
            $table->rememberToken();
            $table->softDeletes();
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('profiles');
    }
};
