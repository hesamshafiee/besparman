<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mockup extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'canvas_width',
        'canvas_height',
        'dpi',
        'print_x',
        'print_y',
        'print_width',
        'print_height',
        'print_rotation',
        'fit_mode',
        'layers',
        'preview_bg',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'layers' => 'array',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Scope کاربردی‌ها */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
