<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;


class OperatorRemain extends Model
{
    protected $connection = 'mongodb'; 
    protected $collection = 'operator_remains'; 
    protected $guarded = [];
}
