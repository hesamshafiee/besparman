<?php

namespace App\Notifications\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailSystem extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;
    public $type;
    public $subject;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($message, $type, $subject = 'General Message')
    {
        $explode = explode('br', $message);
        $this->message = $explode;
        $this->type = $type;
        $this->subject = $subject;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if (!$notifiable->hasVerifiedEmail()) {
            return ['database'];
        }

        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = '';

        return (new MailMessage)
            ->subject($this->subject)
            ->markdown('mails.system', ['url' => $url, 'message' => $this->message]);
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
