<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class ReportDailyBalance extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'report_daily_balances';
    protected $fillable = ['user_id', 'data', 'balance'];

}
