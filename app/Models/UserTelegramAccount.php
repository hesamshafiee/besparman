<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTelegramAccount extends Model
{
    protected $fillable = ['user_id', 'telegram_id', 'label','username','name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}