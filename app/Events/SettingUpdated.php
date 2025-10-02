<?php

namespace App\Events;

use App\Models\Setting;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SettingUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $setting;

    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    public function broadcastOn()
    {
        return new Channel('settings');
    }

    public function broadcastWith()
    {
        return $this->setting->toArray();
    }

    public function broadcastAs()
    {
        return 'SettingUpdated';
    }
}
