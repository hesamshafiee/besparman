<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneBook extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    protected $fillable = ['phone_number', 'name', 'last_settings', 'user_id'];
}
