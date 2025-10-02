<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ScheduledTopup;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTopupJob implements ShouldQueue
{
    use Queueable;

    protected int $topupId;

    public function __construct(int $topupId)
    {
        $this->topupId = $topupId;
    }

    public function handle(): void
    {
        $topup = ScheduledTopup::find($this->topupId);

        if (!$topup || $topup->status !== 'pending') {
            return;
        }

        $topup->update(['status' => 'processing']);

        try {
            $payload = json_decode($topup->payload, true);
            $product = Product::find($payload[0]['id']);
            $payload[0] = $product;

            Wallet::payWithoutCart(...$payload);

            $topup->update(['status' => 'done']);
        } catch (\Exception $e) {
            $topup->update(['status' => 'pending']);
            throw $e;
        }
    }
}
