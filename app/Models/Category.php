<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Category extends Model
{
    use HasFactory;

    
    protected $fillable = ['name', 'parent_id', 'data', 'status', 'default_setting'];

    protected $casts = [
        'data' => 'array',
        'default_setting' => 'array',
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
