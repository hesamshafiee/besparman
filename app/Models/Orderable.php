<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orderable extends Model
{
    protected $table = 'orderables';

    protected $fillable = [
        'order_id',
        'orderable_id',
        'orderable_type',
        'price',
        'quantity',
        'discount',
        'product_snapshot',
        'config',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'config'           => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderable()
    {
        return $this->morphTo();
    }
}
