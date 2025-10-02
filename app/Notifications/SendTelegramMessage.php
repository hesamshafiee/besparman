<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Http;

class SendTelegramMessage
{
    public function sendTelegramMessage(string $message, string $botToken = '8250209049:AAFPePvBkjDF5fJpOzcoMpUDg4qSjvzUC78'): void
    {
        $chatIds = ['95055874', '88052890', '703249510', '452493497'];

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $proxy = 'http://maraton:ali110Ali110@registry.abrbit.com:3128';

        foreach ($chatIds as $chatId) {
            Http::withOptions([
                'proxy' => $proxy,
                'timeout' => 10,
                'verify' => false,
            ])->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        }
    }

    public function sendTelegramMessageForUsers($message,$chatId): void
    {
        if(!$chatId){
            return ;
        }
        $token = '7963455135:AAG9_lHrrq5D5ryUAcABcp34uvz9d9JSCOQ' ;
        if(env('APP_ENV')=='development'){
           $token = '8341466921:AAGOh3IXbtiOLdOQGlnNkL6PUEGyuEWLPuc' ;
        }
        
        $proxy = 'http://maraton:ali110Ali110@registry.abrbit.com:3128';
        $url = "https://api.telegram.org/bot{$token}/sendMessage";


        Http::withOptions([
                'proxy' => $proxy,
                'timeout' => 10,
                'verify' => false,
            ])->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);

    }
}
