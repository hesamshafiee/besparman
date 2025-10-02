<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransactionExtra extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;

    protected $fillable = ['taken_value', 'value', 'mobile'];
}
