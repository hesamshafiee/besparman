<?php

namespace App\Jobs;

use App\Events\UserNotification;
use App\Models\Operator;
use App\Models\Product;
use App\Models\User;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TopUpGroupJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 1;

    private int $topUpGroupId;
    private int $product_id;
    private User $user;
    private string $mobile;
    private string $price;
    private string $refCode;
    private string $offerCode;
    private string $offerType;
    private string $takenValue;
    private string $type;
    private string $ext_id;
    private string $webserviceCode;
    public string $userMobile;

    public function __construct(
        int $topUpGroupId,
        int $product_id,
        string $mobile,
        string $price,
        string $refCode,
        string $offerCode,
        string $offerType,
        string $takenValue,
        string $type,
        int $ext_id,
        $webserviceCode,
        User $user
    ) {
        $this->topUpGroupId = $topUpGroupId;
        $this->product_id = $product_id;
        $this->mobile = $mobile;
        $this->price = $price;
        $this->refCode = $refCode;
        $this->offerCode = $offerCode;
        $this->offerType = $offerType;
        $this->takenValue = $takenValue;
        $this->type = $type;
        $this->ext_id = $ext_id;
        $this->webserviceCode = $webserviceCode;
        $this->userMobile = $user->mobile;
        $this->user = $user;
    }

    public function handle(): void
    {
        $product = Product::findOrFail($this->product_id);
        try {
            $response = Wallet::payWithoutCart(
                $product,
                $this->mobile,
                $this->price ?? 0,
                $this->offerCode,
                $this->offerType,
                $this->takenValue ?? null,
                Operator::getOperatorType($product),
                $this->ext_id ?? 59,
                '',
                false,
                '',
                $this->userMobile,
                false,
                null,
                $this->topUpGroupId,
                null
            );

            $ok = !empty($response['status']);

            Redis::pipeline(function ($pipe) use ($ok) {
                $pipe->incr("gcr:{$this->topUpGroupId}:done");
                $pipe->incr("gcr:{$this->topUpGroupId}:" . ($ok ? 'success' : 'fail'));
            });

            $done    = (int) Redis::get("gcr:{$this->topUpGroupId}:done");
            $total   = (int) Redis::get("gcr:{$this->topUpGroupId}:total");
            $success = (int) Redis::get("gcr:{$this->topUpGroupId}:success");
            $fail    = (int) Redis::get("gcr:{$this->topUpGroupId}:fail");
            $progress = $total ? (int) floor(($done / $total) * 100) : 0;

            $message = [
                'type'            => 'group_charge.progress',
                'group_charge_id' => $this->topUpGroupId,
                'mobile'          => $this->mobile,
                'status'          => $ok ? 'success' : 'fail',
                'counters'        => compact('done','total','success','fail','progress'),
                'text'            => $ok
                    ? "Top-up succeeded for {$this->mobile}"
                    : "Top-up failed for {$this->mobile}",
            ];

            event(new UserNotification($this->user, $message));

        } catch (\Throwable $e) {
            Log::error("TopUpGroupJob FAILED: GroupCharge ID {$this->topUpGroupId}, Mobile {$this->mobile}, Error: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
