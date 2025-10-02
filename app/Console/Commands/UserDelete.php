<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UserDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userIds = User::where('type', 'ordinary')
            ->whereNull('mobile_verified_at')
            ->whereNull('profile_confirm')
            ->whereNull('password')
            ->whereNull('deleted_at')
            ->whereDoesntHave('orders')
            ->where(function($query) {
                $query->doesntHave('wallets')
                    ->orWhereHas('wallets', function($q) {
                        $q->where('value', 0);
                    });
            })->pluck('id');


        if ($userIds->isNotEmpty()) {
            DB::transaction(function() use ($userIds) {
                DB::table('report_daily_balances')->whereIn('user_id', $userIds)->delete();
                DB::table('wallets')->whereIn('user_id', $userIds)->delete();
                DB::table('verification')->whereIn('user_id', $userIds)->delete();

                User::whereIn('id', $userIds)->forceDelete();
            });


            $count = $userIds->count();
            echo "Deleted $count users and their related records.";
        } else {
            echo "No matching users found.";
        }
    }
}
