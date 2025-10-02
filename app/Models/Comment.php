<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
}
