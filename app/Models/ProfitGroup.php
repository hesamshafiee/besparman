<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitGroup extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    protected $fillable = [
        'title',
        'profit_split_ids'
        ];

    protected $casts = [
        'profit_split_ids' => 'array',
    ];
}
