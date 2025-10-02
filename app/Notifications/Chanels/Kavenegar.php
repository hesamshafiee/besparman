<?php

namespace App\Notifications\Chanels;

use \Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use App\Jobs\SendTelegramMessageJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Kavenegar
{
    public function send($notifibale, Notification $notification) : Response
    {
        if (optional($notification)->code) {
            $number = $notification->number;
            $code = $notification->code;
            $response = Http::get(env('KAVENEGAR_URL') . env('KAVENEGAR_TOKEN') .
                '/verify/lookup.json?receptor=' . $number . '&token=' . $code . '&template=' .
                env('KAVENEGAR_TEMPLATE'));
            $mobile = substr($number, 2);
            $user = User::getLoggedInUserOrGetFromGivenMobile($mobile);
            $otp_telegram = $user->settings->settings['otp_telegram'] ?? 0;
            if($otp_telegram){
                $message = "کد فعالسازی: {$code}\nایساج";
                foreach ($user->telegramAccounts as $telegramAccount) {
                        SendTelegramMessageJob::dispatch($message, $telegramAccount->telegram_id)->onQueue('telegram');
                    }
            }



        } else {
            $mobile = $notifibale->mobile;
            $response = Http::get(env('KAVENEGAR_URL') . env('KAVENEGAR_TOKEN') .
            '/sms/send.json?receptor=' . $mobile . '&message=' . $notification->toKavenegarSms());
        }
        return $response;
    }
}
