<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Predis\Command\Redis\PUBLISH;

class Work extends Model
{
    use HasFactory, SoftDeletes;

    const IS_PUBLISHED_TRUE = 1 ;
    const IS_PUBLISHED_FALSE= 0 ;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'image_path',
        'thumb_path',
        'is_published',
        'published_at',
    ];


    protected $dates = [
        'deleted_at'
    ];


    protected $casts = [
        'is_published' => 'integer',
        'published_at' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public static function makeSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }
}
