<?php

namespace App\Notifications\V1;

use App\Notifications\Chanels\Kavenegar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use JetBrains\PhpStorm\ArrayShape;

class Otp extends Notification implements ShouldQueue
{
    use Queueable;

    public string $code;
    public string $number;

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array
     */
    public function viaQueues(): array
    {
        return [
            Kavenegar::class => 'sms',
            'database' => 'low',
        ];
    }

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $code, string $number)
    {
        $this->code = $code;
        $this->number = '00' . $number;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
        public function via(mixed $notifiable) :array
    {
        if (env('APP_ENV') === 'production' || env('APP_ENV') === 'development') {
            return [Kavenegar::class, 'database'];
        }
        return ['database'];
    }

    /**
     * @param $notifiable
     * @return string[]
     */
    #[ArrayShape(['code' => "string"])] public function toDatabase($notifiable) :array
    {
        return [
            'code' => $this->code,
        ];
    }
}
