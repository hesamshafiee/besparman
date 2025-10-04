<?php

namespace App\Console\Commands;

use App\Jobs\UserReportJob;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Console\Command;

class UserReport extends Command
{
    protected $signature = 'app:user-report
                            {startDate? : Start date (Y-m-d)}
                            {endDate? : End date (Y-m-d)}';

    protected $description = 'Generate user report for a date range (optional parameters)';

    public function handle()
    {
        $startDate = $this->argument('startDate')
            ? new DateTime($this->argument('startDate'))
            : new DateTime('yesterday');

        $endDate = $this->argument('endDate')
            ? new DateTime($this->argument('endDate'))
            : clone $startDate;

        for ($date = $startDate; $date <= $endDate; $date->add(new DateInterval('P1D'))) {
            $formattedDate = $date->format('Y-m-d');
            $this->info("📌 Dispatching job for {$formattedDate}");

            UserReportJob::dispatch($formattedDate)->onQueue('report');
        }

        $this->info("✅ All jobs dispatched.");
    }
}
