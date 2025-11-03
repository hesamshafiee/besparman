<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;


class Work extends Model
{
    use HasFactory, SoftDeletes;


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
        'is_published' => 'boolean',
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
}
