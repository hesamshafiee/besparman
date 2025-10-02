<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateToken
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $token;
    public $ip;
    public $agent;

    public function __construct($user, $token, $ip, $agent)
    {
        $this->user = $user;
        $this->token = $token;
        $this->ip = $ip;
        $this->agent = $agent;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('channel-name');
    }
}
