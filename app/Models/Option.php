<?php



// app/Models/Option.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Option extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_required',
        'is_active',
        'meta',
        'sort_order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
        'meta'        => 'array',
    ];

    public function values()
    {
        return $this->hasMany(OptionValue::class)->orderBy('sort_order');
    }

    public function categories()
    {
        // پیوت مدل‌دار: CategoryOption
        return $this->belongsToMany(Category::class, 'category_option')
            ->using(CategoryOption::class)
            ->withPivot(['is_required', 'is_active', 'sort_order'])
            ->withTimestamps();
    }

    

    /** اسکوپ‌های کاربردی */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order');
    }
}
