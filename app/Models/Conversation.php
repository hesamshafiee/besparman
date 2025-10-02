<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\SortableTrait;

class Conversation extends Model
{
    use HasFactory, SortableTrait;
    use LogsActivityWithRequest;

    protected $fillable = [
        'product_id',
        'count',
        'weight',
        'price',
        'expiry_date',
        'warehouse_address',
        'source',
        ];
}
