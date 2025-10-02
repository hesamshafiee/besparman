<?php

use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventColumnToActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $collection) {
                $collection->index('event');
            });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $collection) {
                try {
                    $collection->dropIndex('event');
                } catch (\Exception $e) {
                    // Ignore "index not found" errors
                    if (strpos($e->getMessage(), 'index not found') === false) {
                        throw $e;
                    }
                }
            });
    }
}
