<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mockups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('slug', 180)->unique();

            // بوم اصلی (بر حسب پیکسل)
            $table->unsignedInteger('canvas_width');   // مثلا 4500
            $table->unsignedInteger('canvas_height');  // مثلا 5400
            $table->unsignedSmallInteger('dpi')->default(300);

            // ناحیه‌ی چاپ/طرح (بر حسب پیکسل، نسبت به بوم)
            $table->integer('print_x');      // offset X از گوشه چپ بالا
            $table->integer('print_y');      // offset Y
            $table->unsignedInteger('print_width');
            $table->unsignedInteger('print_height');

            // تنظیمات چیدمان طرح
            $table->unsignedSmallInteger('print_rotation')->default(0); // درجه
            $table->enum('fit_mode', ['contain', 'cover', 'stretch'])->default('contain');

            // فایل‌ها/لایه‌ها
            // می‌تونی base, overlay, shadow, mask رو نگه داری
            $table->json('layers')->nullable();

            // تنظیمات نمایش
            $table->string('preview_bg', 9)->nullable(); // مثلا "#FFFFFF" یا "transparent"
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);

            $table->timestamps();

            // یک دسته نمی‌تونه دو موکاپ با یک اسم داشته باشه (اختیاری)
            $table->unique(['category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mockups');
    }
};
