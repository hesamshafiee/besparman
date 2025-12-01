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
        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100); // مثلاً "قرمز"
            $table->string('code', 50);  // مثلاً "red"
            $table->json('meta')->nullable(); // { "color":"#FF0000", "price_modifier":10000 }
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->timestamps();
             $table->softDeletes();

            $table->unique(['option_id', 'code']);
            $table->index(['option_id','is_active','sort_order']);
                        // ستون‌های محاسباتی (Generated) برای جستجو/مرتب‌سازی سریع روی JSON
            $table->decimal('price_modifier', 14, 2)->virtualAs("JSON_EXTRACT(`meta`, '$.price_modifier')")->nullable();
            $table->decimal('weight_modifier', 14, 3)->virtualAs("JSON_EXTRACT(`meta`, '$.weight_modifier')")->nullable();
            $table->index('price_modifier');
            $table->index('weight_modifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_values');
    }
};
