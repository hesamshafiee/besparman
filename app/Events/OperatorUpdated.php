<?php

namespace App\Events;

use App\Models\Operator;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OperatorUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $operator;

    public function __construct(Operator $operator)
    {
        $this->operator = $operator;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('operators');
    }

    public function broadcastWith(): array
    {
        return $this->operator->toArray();
    }

    public function broadcastAs(): string
    {
        return 'OperatorUpdated';
    }
}
