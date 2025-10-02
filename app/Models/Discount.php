<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;

    const TYPE_PERCENT = 'percent';
    const TYPE_MONEY = 'money';
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = ['code', 'type', 'value', 'reusable', 'status', 'expire_at', 'min_purchase', 'max_purchase'];

    public function users()
    {
        return $this->morphedByMany(User::class, 'discountable');
    }

    public function products()
    {
        return $this->morphedByMany(Product::class, 'discountable');
    }
}
