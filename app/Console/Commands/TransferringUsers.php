<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransferringUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transferring-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        User::onlyTrashed()->where('deleted_at', Carbon::create(2012, 12, 12, 12, 12, 12))->chunk(1000, function ($users) {
            foreach ($users as $user) {
                $response = Http::get('https://esaj.ir/site/getuserbyid?id=' . $user->id);
                $walletValue  = $response->json('wallet_value');
                if ($walletValue) {
                    $point  = (int) ceil($response->json('point'));
                    $batchArray = [];

                    foreach ($response->json('phone_book') as $index => $number) {
                        $batchArray[$index]['name'] = $number['name'];
                        $batchArray[$index]['phone_number'] = $number['mobile'];
                        $batchArray[$index]['user_id'] = $user->id;
                        $batchArray[$index]['last_settings'] = '{}';
                    }

                    $walletTransaction = WalletTransaction::where('user_id', $user->id)->firstOrFail();
                    $walletTransaction->value = $walletValue;
                    $walletTransaction->wallet_value_after_transaction = $walletValue;
                    $walletTransaction->sign = $walletTransaction->sign();

                    DB::transaction(function () use ($user, $batchArray, $point, $walletValue, $walletTransaction) {
                        $user->points = $point;
                        $user->deleted_at = null;
                        $wallet = $user->wallet;
                        $wallet->value = $walletValue;
                        $chunkSize = 100;
                        $insertStatus = true;

                        foreach (array_chunk($batchArray, $chunkSize) as $chunk) {
                            DB::table('phone_books')->insertOrIgnore($chunk);
                        }

                        $user->save();
                        $walletTransaction->save();
                        $wallet->save();
                    });
                }
            }
        });
    }
}
