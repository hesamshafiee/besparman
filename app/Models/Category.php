<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = ['name', 'parent_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    /** 🔁 رابطه با دسته والد */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** 🔁 زیرمجموعه‌ها */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /** 🔁 محصولات مرتبط */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product')
                    ->withPivot('address');
    }

    /** 🧩 ساخت درخت دسته‌بندی (در صورت نیاز در resource استفاده می‌شود) */
    public static function tree()
    {
        return self::with('children')->whereNull('parent_id')->get();
    }
}
