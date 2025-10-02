<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledTopup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'scheduled_at',
        'status',
        'payload',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
