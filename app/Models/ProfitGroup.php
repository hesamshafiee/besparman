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
        'designer_profit',
        'site_profit',
        'referrer_profit',
    ];

}
