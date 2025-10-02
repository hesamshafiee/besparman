<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail(int $id)
 */
class Category extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    protected $fillable = ['name', 'data'];
    protected $casts = ['data' => 'array'];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'categorizable')->withPivot('address');
    }
}
