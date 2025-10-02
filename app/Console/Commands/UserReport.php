<?php

namespace App\Console\Commands;

use App\Jobs\UserReportJob;
use App\Models\User;
use App\Models\ReportDailyUser;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Console\Command;
use MongoDB\BSON\UTCDateTime;

class UserReport extends Command
{
    protected $signature = 'app:user-report
                            {date? : The first date parameter (optional)}';

    protected $description = 'Generate user report for one date (parameter is optional)';

    public function handle()
    {
        $startDate = new DateTime('2025-07-29');
        $endDate   = new DateTime('2025-09-30');

        for ($date = $startDate; $date <= $endDate; $date->add(new DateInterval('P1D'))) {
            $formattedDate = $date->format('Y-m-d');
            $this->info("Processing date: {$formattedDate}");

            User::chunk(50, function ($users) use ($formattedDate) {
                $startOfDayMysql = Carbon::parse($formattedDate)->startOfDay()->toDateTime();
                $endOfDayMysql = Carbon::parse($formattedDate)->endOfDay()->toDateTime();
                $startOfDay = new UTCDateTime(Carbon::parse($formattedDate)->startOfDay()->timestamp * 1000);
                $endOfDay   = new UTCDateTime(Carbon::parse($formattedDate)->endOfDay()->timestamp * 1000);

                foreach ($users as $user) {
                    $exists = ReportDailyUser::where('user_id', $user->id)
                        ->whereBetween('date', [$startOfDay, $endOfDay])
                        ->exists();

                    if (!$exists) {
                        $transaction = WalletTransaction::where('user_id', $user->id)
                            ->where('detail', WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER)
                            ->where('third_party_status', 1)
                            ->where('type', 'decrease')
                            ->whereBetween('created_at', [$startOfDayMysql, $endOfDayMysql])
                            ->exists();

                        if($transaction) {
                            UserReportJob::dispatch($user, $formattedDate)->onQueue('report');
                            $this->info("✅ Dispatched job for User #{$user->id} on {$formattedDate}");
                        }

                    } else {
                        $this->warn("⏭️ Skipped User #{$user->id} on {$formattedDate} (already exists)");
                    }
                }
            });
        }

        $this->info("User report job dispatch finished.");
    }
}
