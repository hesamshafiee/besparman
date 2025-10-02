<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserNotification implements ShouldBroadcast
{
    use SerializesModels;

    public $message;
    public $user;

    public function __construct(User $user, array $message)
    {
        $this->message = $message;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->user->id);
    }

    public function broadcastAs()
    {
        return 'UserNotification';
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message
        ];
    }
}
