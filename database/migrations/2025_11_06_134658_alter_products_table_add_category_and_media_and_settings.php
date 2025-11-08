<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('user_id')->constrained('categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug', 190)->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('products', 'category_name')) {
                $table->string('category_name', 255)->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'original_path')) {
                $table->string('original_path', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'preview_path')) {
                $table->string('preview_path', 255)->nullable()->after('original_path');
            }
            if (!Schema::hasColumn('products', 'settings')) {
                $table->json('settings')->nullable()->after('preview_path');
            }

            try { $table->unsignedBigInteger('price')->nullable(false)->change(); } catch (\Throwable $e) {}
            try { $table->unsignedBigInteger('second_price')->nullable()->change(); } catch (\Throwable $e) {}

            if (!Schema::hasColumn('products', 'status')) {
                $table->unsignedTinyInteger('status')->default(1)->after('price');
            }

            $table->index(['user_id']);
            $table->index(['category_id']);
            $table->index(['status']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            try { $table->dropConstrainedForeignId('user_id'); } catch (\Throwable $e) {}
            try { $table->dropConstrainedForeignId('category_id'); } catch (\Throwable $e) {}
            foreach (['slug','category_name','original_path','preview_path','settings'] as $col) {
                if (Schema::hasColumn('products', $col)) $table->dropColumn($col);
            }
        });
    }
};
