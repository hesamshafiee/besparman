<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

class LogoutListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event): void
    {
        activity()
            ->causedBy('system')
            ->withProperties([
                'user_id' => $event->user->id,
                'impersonated_by' => session()->get('impersonateId', null),
                'type' => 'logout',
                'ip' => request()->ip(),
                'agent' => request()->userAgent(),
                'created_at' => now()
            ])
            ->event('logout')
            ->log('logout');
    }
}
