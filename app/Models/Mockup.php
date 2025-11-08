<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Mockup extends Model
{
    use HasFactory;

    
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
        'layers',       // JSON: {base,overlay,shadow,mask}
        'preview_bg',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'layers'    => 'array',
        'is_active' => 'boolean',
        'dpi'       => 'integer',
        'canvas_width'  => 'integer',
        'canvas_height' => 'integer',
        'print_x'       => 'integer',
        'print_y'       => 'integer',
        'print_width'   => 'integer',
        'print_height'  => 'integer',
        'print_rotation'=> 'integer',
        'sort'          => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
