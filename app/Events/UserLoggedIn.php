<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Auth\Authenticatable;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public $user;
    public $ip;

    public function __construct(Authenticatable $user, string $ip)
    {
        $this->user = $user;
        $this->ip = $ip;
    }
}
