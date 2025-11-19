<?php

// app/Models/OptionValue.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptionValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'option_id',
        'name',
        'code',
        'is_active',
        'meta',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta'      => 'array',
    ];

    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'variant_option_value')
            ->withTimestamps();
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order');
    }
}
