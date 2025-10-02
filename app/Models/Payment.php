<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    const STATUSCANCELED = 'canceled';
    const STATUSPAID = 'paid';
    const STATUSUNPAID = 'unpaid';
    const STATUSREJECT = 'reject';
    const STATUSRETURNED = 'returned';
    Const BANKSTATEOK = 'OK';
    Const BANKSTATECANCELED = 'canceled';

    const TYPE_ONLINE = 'online';
    const TYPE_CARD = 'card';

    use HasFactory;
    use LogsActivityWithRequest;

    protected $fillable = ['resnumber', 'price', 'status', 'state', 'type', 'confirmed_by', 'user_id', 'return_url', 'order_id', 'bank_name'];

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $this->order_id ?? ''
            . $this->price
            . $this->resnumber ?? ''
            . $this->refnumber ?? ''
            . $this->type
            . $this->status
            . $this->bank_name ?? ''
            . $this->bank_info ?? '';
    }
}
