<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // چک کن اگر ایندکس unique روی product_id هنوز ساخته نشده، بسازش
        $indexExists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'warehouses'
              AND index_name = 'warehouses_product_id_unique'
        ");

        if (!$indexExists || $indexExists->cnt == 0) {
            Schema::table('warehouses', function (Blueprint $table) {
                // اسم ایندکس رو مشخص می‌کنیم تا با همون اسم قابل چک باشه
                $table->unique('product_id', 'warehouses_product_id_unique');
            });
        }
    }

    public function down(): void
    {
        // 1. اول هر foreign key ای که روی ستون product_id هست رو drop می‌کنیم
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'warehouses'
              AND COLUMN_NAME = 'product_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($foreignKeys as $fk) {
            $fkName = $fk->CONSTRAINT_NAME;
            // اگر FK وجود داشته باشد، بندازش
            DB::statement("ALTER TABLE `warehouses` DROP FOREIGN KEY `$fkName`");
        }

        // 2. حالا ایندکس unique رو فقط اگر واقعاً هست حذف می‌کنیم
        $indexExists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'warehouses'
              AND index_name = 'warehouses_product_id_unique'
        ");

        if ($indexExists && $indexExists->cnt > 0) {
            DB::statement("ALTER TABLE `warehouses` DROP INDEX `warehouses_product_id_unique`");
        }


    }
};
