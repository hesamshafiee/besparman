<?php

namespace App\Console\Commands;

use App\Models\ReportDailyBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToMongoDB extends Command
{
    protected $signature = 'migrate:mongodb';

    protected $description = 'Migrate MySQL data to MongoDB with chunking support';

    public function handle()
    {
        DB::table('report_daily_balances')
            ->orderBy('id')
            ->chunk(1000, function ($rows) {
                $documents = [];

                foreach ($rows as $row) {
                    $documents[] = (array) $row;
                }

                ReportDailyBalance::insert($documents);
            });
    }
}
