<?php

namespace App\Events;

use App\Models\Version;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class VersionUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $version;

    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    public function broadcastOn()
    {
        return new Channel('versions');
    }

    public function broadcastWith()
    {
        return $this->version->toArray();
    }

    public function broadcastAs()
    {
        return 'VersionUpdated';
    }
}
