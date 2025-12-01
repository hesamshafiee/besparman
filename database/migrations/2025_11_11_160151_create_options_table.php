<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // نام نمایشی مثل "رنگ"
            $table->string('code', 50)->unique(); // مثل "color"
            $table->enum('type', ['select', 'text', 'number', 'color', 'boolean'])->default('select');
            $table->string('display_type', 30)->nullable(); // برای UI (مثلاً color-picker)
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable(); // هر داده‌ی اضافی سراسری
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
