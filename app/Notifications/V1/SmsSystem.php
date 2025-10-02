<?php

namespace App\Notifications\V1;

use App\Notifications\Chanels\Kavenegar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SmsSystem extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;
    public $type;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($message, $type)
    {
        $this->message = $message;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if (env('APP_ENV') === 'production' || env('APP_ENV') === 'development') {
            return [Kavenegar::class, 'database'];
        }

        return ['database'];
    }

    public function toKavenegarSms()
    {
        return $this->message . "\n" . __('sms.cancellation');
    }

    /**
     * @param $notifiable
     * @return int[]
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message,
            'userId' => $notifiable->id,
        ];
    }
}
