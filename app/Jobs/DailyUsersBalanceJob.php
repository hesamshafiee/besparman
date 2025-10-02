<?php

namespace App\Jobs;

use App\Models\ReportDailyBalance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DailyUsersBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function handle()
    {
        $transactions = DB::table(DB::raw("(
                SELECT wallet_transactions.*,
                       ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC) AS row_num
                FROM wallet_transactions
            ) AS subquery"))
            ->where('created_at', '<', Carbon::yesterday()->endOfDay())
            ->whereIn('user_id', $this->users->pluck('id')->toArray())
            ->where('row_num', 1)
            ->get()
            ->keyBy('user_id');

        $reports = [];

        foreach ($this->users as $user) {
            $latestTransaction = $transactions->get($user->id);

            $reports[] = [
                'user_id' => $user->id,
                'balance' => $latestTransaction ? $latestTransaction->wallet_value_after_transaction : 0,
                'date' => Carbon::yesterday(),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        ReportDailyBalance::insert($reports);
    }
}
