<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportDailyUser;

class UpdateReportTotals extends Command
{
    protected $signature = 'update:operator-totals';
    protected $description = 'Update report_daily_users with total and total_original_price fields for each operator';

    public function handle()
    {
        $users = ReportDailyUser::all();

        foreach ($users as $user) {
            if (!isset($user->operators) || !is_array($user->operators)) {
                continue;
            }

            $updates = [];

            foreach ($user->operators as $operatorId => $operatorData) {
                if (isset($operatorData['name'])) {
                    $operatorName = strtolower(trim($operatorData['name']));

                    $totalField = "{$operatorName}_total";
                    $originalPriceField = "{$operatorName}_total_original_price";

                    $updates[$totalField] = $operatorData['total'] ?? 0;
                    $updates[$originalPriceField] = $operatorData['total_original_price'] ?? 0;
                }
            }

            if (!empty($updates)) {
                $user->update($updates);
                $this->info("Updated document ID: {$user->_id}");
            }
        }

        $this->info('All documents updated successfully.');
    }
}
