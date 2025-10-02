<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'idempotency_key',
        'user_id',
        'request_hash',
        'response_status',
        'response_headers',
        'response_body',
        'locked_at',
        'expires_at',
    ];

    protected $casts = [
        'response_headers' => 'array',
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
