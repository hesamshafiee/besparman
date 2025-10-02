<?php

namespace App\Console\Commands;

use App\Models\GroupCharge;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;

class CorrectingGroupCharges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:correct-group {id? : The first id parameter (id)}';

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
        $id = $this->argument('id');

        $model = GroupCharge::find($id);

        $transactions = WalletTransaction::select('group_charge_id', 'detail', 'third_party_status', 'charged_mobile')
            ->where('group_charge_id', $model->id)
            ->where('detail', WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER)
            ->get();

        $successfulChargedMobiles = $transactions->where('third_party_status', true)->pluck('charged_mobile');
        $failedChargedMobiles = $transactions->where('third_party_status', false)->pluck('charged_mobile');

        $model->update([
            'charge_status' => GroupCharge::CHARGE_STATUS_DONE,
            'phone_numbers_successful' => $successfulChargedMobiles,
            'phone_numbers_unsuccessful' => $failedChargedMobiles
        ]);
    }
}
