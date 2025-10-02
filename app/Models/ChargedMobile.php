<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargedMobile extends Model
{
    protected $table = 'charged_mobiles';

    public $incrementing = false; 

    protected $primaryKey = null; 

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'mobile'];
    
    public $timestamps = false;

}
