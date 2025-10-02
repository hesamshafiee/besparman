<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $casts = [
        'settings' => AsArrayObject::class,
    ];
}
