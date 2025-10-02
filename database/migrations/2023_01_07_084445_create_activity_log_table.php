<?php

use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->create(config('activitylog.table_name'), function (Blueprint $collection) {
                // MongoDB uses "_id" as the primary key by default (no need for bigIncrements)
                $collection->index('log_name'); // Indexes improve query performance
                $collection->index('subject_id');
                $collection->index('subject_type');
                $collection->index('causer_id');
                $collection->index('causer_type');
                $collection->index('created_at');
            });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name'));
    }
}
