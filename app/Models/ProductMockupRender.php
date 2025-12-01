<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMockupRender extends Model
{
    protected $fillable = ['product_id', 'mockup_id', 'path', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function mockup()
    {
        return $this->belongsTo(Mockup::class);
    }
}
