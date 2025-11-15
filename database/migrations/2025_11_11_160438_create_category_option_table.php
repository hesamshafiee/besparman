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
        Schema::create('category_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->nullable(); // اگر null => از options.is_required تبعیت می‌کند
            $table->boolean('is_active')->default(true); // فعال/غیرفعال فقط در این دسته
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id','option_id']);
            $table->index(['category_id','is_active','sort_order']);        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_option');
    }
};
