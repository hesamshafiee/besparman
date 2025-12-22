<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Order extends Model
{
    const STATUSCANCELED = 'canceled';
    const STATUSRECEIVED = 'received';
    const STATUSPOSTED = 'posted';
    const STATUSPREPARATION = 'preparation';
    const STATUSPAID = 'paid';
    const STATUSRESERVED = 'reserved';
    const STATUSUNPAID = 'unpaid';

    use HasFactory;
    use LogsActivityWithRequest;


    protected $fillable = [
        'status',
        'price',
        'tracking_serial',
        'type',
        'store',
        'total_price',
        'total_discount',
        'from_wallet',
        'discount_id',
        'sale_id',
        'comment',
        'total_sale',
        'sale_id',
    ];

    /**
     * @return mixed
     */
    public function user(): mixed
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return MorphToMany
     */
    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'orderable')->withPivot('quantity', 'discount', 'price');
    }

    /**
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return BelongsTo
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * @return BelongsTo
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
    public function items()
    {
        return $this->hasMany(Orderable::class);
    }
}
