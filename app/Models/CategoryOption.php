<?php


// app/Models/CategoryOption.php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryOption extends Pivot
{
    protected $table = 'category_option';

    protected $fillable = [
        'category_id',
        'option_id',
        'is_required',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    /** محاسبه الزامی بودن با درنظر گرفتن override */
    public function getEffectiveIsRequiredAttribute(): bool
    {
        // اگر در پیوت null بود، از مقدار option استفاده کن
        return $this->is_required ?? (bool) optional($this->option)->is_required;
    }

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
