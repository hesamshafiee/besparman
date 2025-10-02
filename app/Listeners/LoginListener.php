<?php

namespace App\Listeners;

use App\Jobs\SendTelegramMessageJob;
use App\Notifications\SendTelegramMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Morilog\Jalali\Jalalian;use Illuminate\Support\Facades\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LoginListener implements ShouldQueue
{

    /**
     * @param $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle($event): void
    {
        $user = $event->user;
        $user->last_login = $event->ip . '/' . now();
        $user->save();

        $tokenModel = $event->token;
        $tokenModel->ip_address = $event->ip;
        $tokenModel->user_agent    = $this->parseBrowser($event->agent);
        $tokenModel->save();

        if($user->telegramAccounts()->count()>0){
            $browser = $this->parseBrowser($event->agent);
            $persianDate = Jalalian::now()->format('Y/m/d H:i:s');
            $message  = "🔔 ورود به پنل ایساج\n\n";
            $message .= "👤 شماره موبایل: " . $user->mobile . "\n";
            $message .= "⏰ زمان ورود: " . $persianDate . "\n";
            $message .= "🌐 آی پی: " . $event->ip . "\n";
            $message .= "💻 مرورگر: " . $browser . "\n";

            foreach ($user->telegramAccounts as $telegramAccount) {
                SendTelegramMessageJob::dispatch($message, $telegramAccount->telegram_id)->onQueue('telegram');
            }
        }
        activity()
            ->withProperties([
                'user_id' => $event->user->id,
                'impersonated_by' => session()->get('impersonateId', null),
                'type' => 'login',
                'ip' => $event->ip,
                'path' => request()->path(),
                'agent' => request()->userAgent(),
                'created_at' => now()
            ])
            ->event('login')
            ->log('login');
    }


    private function parseBrowser($userAgent)
    {
        $browsers = [
            // Main browsers
            '/Edg/i'              => 'Edge',
            '/OPR|Opera/i'        => 'Opera',
            '/Firefox/i'          => 'Firefox',
            '/Chrome/i'           => 'Chrome',
            '/Safari/i'           => 'Safari',
            '/MSIE|Trident/i'     => 'Internet Explorer',

            // API clients / HTTP tools
            '/PostmanRuntime/i'   => 'Postman',
            '/curl/i'             => 'cURL',
            '/HTTPie/i'           => 'HTTPie',

            // Bots / crawlers
            '/Googlebot/i'        => 'Googlebot',
            '/Bingbot/i'          => 'Bingbot',
            '/Slurp/i'            => 'Yahoo! Slurp',
            '/DuckDuckBot/i'      => 'DuckDuckGo Bot',
            '/Baiduspider/i'      => 'Baidu Spider',
            '/YandexBot/i'        => 'Yandex Bot',
            '/Sogou/i'            => 'Sogou Spider',
            '/Exabot/i'           => 'Exabot',
            '/facebot/i'          => 'Facebook Bot',
            '/ia_archiver/i'      => 'Alexa Crawler'
        ];

        foreach ($browsers as $regex => $name) {
            if (preg_match($regex, $userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }
}
