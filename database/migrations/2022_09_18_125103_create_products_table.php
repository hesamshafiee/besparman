<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // روابط
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('work_id')->nullable()->constrained('works')->nullOnDelete();

            // پایه
            $table->string('name', 200)->nullable();
            $table->string('slug', 220)->unique();
            $table->string('name_en')->nullable();

            // توضیحات
            $table->text('description')->nullable();
            $table->longText('description_full')->nullable();

            // کد/قیمت/نوع
            $table->string('sku', 100)->nullable()->unique();
            $table->unsignedBigInteger('price')->default(0);
            $table->char('currency', 3)->default('IRR');
            $table->string('type', 50)->default('standard');

            // تنظیمات فروش/نمایش
            $table->unsignedSmallInteger('minimum_sale')->nullable();
            $table->string('dimension', 50)->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedTinyInteger('status')->default(0);

            // سورت
            $table->unsignedInteger('sort')->nullable()->index();

            // فایل‌ها
            $table->string('original_path')->nullable();
            $table->string('preview_path')->nullable();

            // JSONها
            $table->json('settings')->nullable();
            $table->json('options')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ایندکس‌ها
            $table->index(['status', 'category_id']);
            // $table->index('user_id');  // لازم نیست؛ برای FK ایندکس ضمنی داریم
            // $table->index('slug');     // زائد است چون unique دارد
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
