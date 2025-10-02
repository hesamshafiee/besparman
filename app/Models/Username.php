<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\SortableTrait;

class Username extends Model
{
    use HasFactory, SortableTrait;
    use LogsActivityWithRequest;


    protected $fillable = [
        'phone',
        'username'
        ];
}
