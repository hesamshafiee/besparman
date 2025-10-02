<?php

namespace App\Console\Commands;

use App\Jobs\TopUpGroupJob;
use App\Models\GroupCharge;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopupGroup extends Command
{
    protected $signature = 'topupgroup:process';
    protected $description = 'Process multiple top-up group charges, including regular and package top-ups.';

    public function handle()
    {
        try {
            $cutoff = now()->subSeconds(GroupCharge::TIMESECONDALLOWEDFORCANCELLATION);
            $groupTypes = [GroupCharge::TYPE_TOPUP, GroupCharge::TYPE_TOPUP_PACKAGE];

            $models = GroupCharge::where('charge_status', GroupCharge::CHARGE_STATUS_PENDING)
                ->whereIn('group_type', $groupTypes)
                ->where(function ($query) use ($cutoff) {
                    $query->where('created_at', '<', $cutoff)
                        ->orWhere('force', GroupCharge::CHARGE_FORCE_ACTIVE);
                })
                ->orderBy('id')
                ->get();

            if ($models->isEmpty()) {
                return 0;
            }

            foreach ($models as $model) {
                if (! $model->topup_information) {
                    Log::warning('[topupgroup:process] Missing topup_information for GroupCharge ID ' . $model->id);
                    continue;
                }

                $updated = GroupCharge::where('id', $model->id)
                    ->where('charge_status', GroupCharge::CHARGE_STATUS_PENDING)
                    ->update(['charge_status' => GroupCharge::CHARGE_STATUS_DOING]);

                if (! $updated) {
                    Log::info("[topupgroup:process] Skipped GroupCharge ID {$model->id} - already being processed.");
                    continue;
                }

                $user = User::find($model->user_id);
                if (! $user) {
                    Log::warning("[topupgroup:process] User not found for GroupCharge ID {$model->id}");
                    continue;
                }

                $phoneNumbers = $model->phone_numbers;
                if (is_object($phoneNumbers)) {
                    $phoneNumbers = $phoneNumbers instanceof \Illuminate\Support\Collection ? $phoneNumbers->all() : (array)$phoneNumbers;
                } elseif (is_string($phoneNumbers)) {
                    $phoneNumbers = json_decode($phoneNumbers, true);
                }

                $phoneNumbersArray = is_array($phoneNumbers) ? $phoneNumbers : [$phoneNumbers];
                $chunks = array_chunk($phoneNumbersArray, count($phoneNumbersArray));
                Cache::store('redis')->set("gcr:{$model->id}:total", count($phoneNumbersArray));

                foreach ($chunks as $chunk) {
                    $jobs = [];

                    foreach ($chunk as $mobile_number) {
                        $jobs[] = new TopUpGroupJob(
                            $model->id,
                            $model->topup_information->product_id,
                            $mobile_number,
                            $model->topup_information->price,
                            (string)($model->topup_information->refCode ?? ''),
                            (string)($model->topup_information->offerCode ?? ''),
                            (string)($model->topup_information->offerType ?? ''),
                            $model->topup_information->price,
                            '',
                            59,
                            '',
                            $user
                        );
                    }

                    $queueNumber = Cache::get('queue_number', 1);

                    Bus::batch($jobs)
                        ->name('TopUpGroupBatch-' . $model->id . '-' . microtime(true))
                        ->onQueue('group_' . $queueNumber)
                        ->finally(function (Batch $batch) use ($model) {
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

                            $rawTopup = $model->topup_information;

                            if ($rawTopup instanceof \stdClass) {
                                // Convert stdClass -> array
                                $topupInformation = json_decode(json_encode($rawTopup), true);
                            } else {
                                // Normal decode if it's a string (or something json_decode can handle)
                                $topupInformation = json_decode($rawTopup, true);
                            }

                            $ip = $topupInformation['ip'] ?? null;

                            if ($ip && $ip !== '-') {
                                DB::update('
                                    UPDATE wallet_transactions
                                    SET extra_info = JSON_SET(extra_info, "$.ip", ?)
                                    WHERE group_charge_id = ?
                                      AND JSON_VALID(extra_info)
                                ', [$ip, $model->id]);
                            }
                        })
                        ->dispatch();

                    $queueNumber = $queueNumber >= env('QUEUE_COUNT', 5) ? 1 : $queueNumber + 1;
                    Cache::forever('queue_number', $queueNumber);
                }
            }

            return 0;

        } catch (\Throwable $e) {
            Log::error('[topupgroup:process] FAILED: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
