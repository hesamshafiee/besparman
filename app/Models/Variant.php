<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'stock',
        'add_price',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function mockups()
    {
        return $this->hasMany(Mockup::class);
    }

    public function optionValues()
    {
        return $this->belongsToMany(OptionValue::class, 'variant_option_value')
            ->withTimestamps();
    }
}
