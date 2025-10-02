<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    const TYPE_PERCENT = 'percent';
    const TYPE_MONEY = 'money';
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = ['title', 'value', 'type', 'start_date', 'end_date', 'status'];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];


    public function categories()
    {
        return $this->morphedByMany(Category::class, 'saleable');
    }

    public function products()
    {
        return $this->morphedByMany(Product::class, 'saleable');
    }

}
