<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    protected $fillable = [
        'province',
        'city',
        'birth_date',
        'address',
        'postal_code',
        'profession',
        'education',
        'store_name',
        'gender',
        'phone',
        'national_code',
        'legal_info'
    ];
}
