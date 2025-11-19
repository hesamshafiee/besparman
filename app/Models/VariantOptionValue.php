<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantOptionValue extends Model
{
    use HasFactory;

    protected $table = 'variant_option_value';

    protected $fillable = [
        'variant_id',
        'option_value_id',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class);
    }
}
