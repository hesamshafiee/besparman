<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    use HasFactory;

    const TYPES = ['admin', 'panel'];

    protected $fillable = [
        'type',
        'title',
        'description',
        'version',
        'status'
    ];

    public function setTypeAttribute($value)
    {
        if (!in_array($value, self::TYPES)) {
            throw new \InvalidArgumentException("Invalid type: $value");
        }
        $this->attributes['type'] = $value;
    }
}
