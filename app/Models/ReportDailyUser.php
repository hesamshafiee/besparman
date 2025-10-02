<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class ReportDailyUser extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'report_daily_users';
    protected $fillable = ['irancell_total', 'mci_total', 'rightel_total', 'shatel_total', 'aptel_total', 'irancell_total_original_price', 'mci_total_original_price', 'rightel_total_original_price', 'shatel_total_original_price', 'aptel_total_original_price'];

}
