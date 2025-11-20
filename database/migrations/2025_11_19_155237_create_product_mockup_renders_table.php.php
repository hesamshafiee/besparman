<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_mockup_renders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('mockup_id')->constrained('mockups')->cascadeOnDelete();
            $table->string('path', 255);      // مسیر فایل خروجی
            $table->json('meta')->nullable(); // ابعاد نهایی، زمان رندر، ...
            $table->timestamps();

            $table->unique(['product_id','mockup_id']); // هر موکاپ یک خروجی برای هر محصول
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_mockup_renders');
    }
};
