<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WalletTransaction extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    const TYPE_INCREASE = 'increase';
    const TYPE_DECREASE = 'decrease';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PENDING = 'pending';
    const DETAIL_INCREASE_ADMIN = 'increase_admin';
    const DETAIL_INCREASE_PRIZE = 'increase_prize';
    const DETAIL_INCREASE_ONLINE = 'increase_online';
    const DETAIL_INCREASE_REFUND = 'increase_refund';
    const DETAIL_DECREASE_ONLINE = 'decrease_online';
    const DETAIL_INCREASE_CARD = 'increase_card';
    const DETAIL_INCREASE_TRANSFER = 'increase_transfer';
    const DETAIL_DECREASE_ADMIN = 'decrease_admin';
    const DETAIL_DECREASE_PURCHASE = 'decrease_purchase';
    const DETAIL_DECREASE_PURCHASE_BUYER = 'decrease_purchase_buyer';
    const DETAIL_INCREASE_PURCHASE_ESAJ = 'increase_purchase_esaj';
    const DETAIL_INCREASE_PURCHASE_PRESENTER = 'increase_purchase_presenter';
    const DETAIL_DECREASE_TRANSFER = 'decrease_transfer';
    const ERROR_VALUE = 'error_value';

    protected $casts = [
        'extra_info' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo
     */
    public function transferFrom(): BelongsTo
    {
        return  $this->belongsTo(User::class, 'transfer_from_id');
    }

    /**
     * @return BelongsTo
     */
    public function transferTo(): BelongsTo
    {
        return  $this->belongsTo(User::class, 'transfer_to_id');
    }

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return  $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return  $this->belongsTo(User::class);
    }

    /**
     * @return HasOne
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'transaction_id');
    }



    /**
     * @return string
     */
    public static function walletTransactionNumber(): string
    {
        return time() . mt_rand(100000, 999999) . mt_rand(1000, 9999);
    }

    /**
     * @return string
     */
    public function sign(): string
    {
        return hash_hmac('sha256', $this->makeSign(), env('APP_SECRET_KEY'));
    }

    /**
     * @return bool
     */
    public function checkSign(): bool
    {
        return hash_equals($this->sign, hash_hmac('sha256', $this->makeSign(), env('APP_SECRET_KEY')));
    }

    /**
     * @return string
     */
    private function makeSign(): string
    {
        $orderable_id = null;
        if ($this->order_id) {
            $order = Order::where('id', $this->order_id)->first();
            $orderable_id = $order->products->pluck('id')->implode('-');
        }

        return $this->wallet_id
            . $this->type
            . $this->resnumber ?? ''
            . $this->refnumber ?? ''
            . $this->value
            . $this->confirmed_by ?? ''
            . $this->description ?? ''
            . $this->detail
            . $this->order_id ?? ''
            . $orderable_id ?? '';
    }
}
