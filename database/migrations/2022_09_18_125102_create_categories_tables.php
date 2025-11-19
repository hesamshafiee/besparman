<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('status', 150)->nullable();
            $table->json('data')->nullable();
            $table->json('default_setting')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('markup_price', 10, 2)->default(0);
            $table->tinyInteger('show_in_work')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
