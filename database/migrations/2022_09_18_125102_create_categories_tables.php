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
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('category_product', function (Blueprint $table) {
            // 👇 روش استاندارد جدید لاراول
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->string('address', 100)->nullable();
            $table->primary(['category_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('categories');
    }
};
