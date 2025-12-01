<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            // اگر جدول قبلاً category_id دارد و می‌خواهی حذفش کنی:
            if (Schema::hasColumn('products', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }

            // اضافه‌کردن variant_id
            $table->foreignId('variant_id')
                ->nullable()
                ->after('user_id')
                ->constrained('variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            // برگشت‌زدن variant_id
            if (Schema::hasColumn('products', 'variant_id')) {
                $table->dropForeign(['variant_id']);
                $table->dropColumn('variant_id');
            }

            // برگشت دادن category_id اگر لازم است
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
        });
    }
};
