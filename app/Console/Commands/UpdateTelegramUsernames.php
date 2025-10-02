<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\UserTelegramAccount;

class UpdateTelegramUsernames extends Command
{
    protected $signature = 'telegram:update-usernames';
    protected $description = 'Update username and name for existing telegram accounts';

    protected $botToken;

    public function __construct()
    {
        parent::__construct();
        $this->botToken ='7963455135:AAG9_lHrrq5D5ryUAcABcp34uvz9d9JSCOQ';
    }

    public function handle()
    {
        $accounts = UserTelegramAccount::whereNull('username')->orWhereNull('name')->get();

        $this->info("Found {$accounts->count()} accounts to update.");

        foreach ($accounts as $account) {
            try {
                $proxy = 'http://maraton:ali110Ali110@registry.abrbit.com:3128';
                $url ="https://api.telegram.org/bot{$this->botToken}/getChat";

                $response = Http::withOptions([
                    'proxy' => $proxy,
                    'timeout' => 10,
                    'verify' => false,
                ])->get($url, [
                    'chat_id' => $account->telegram_id,
                ]);

                if ($response->successful() && $response->json('ok')) {
                    $result = $response->json('result');

                    $firstName = $result['first_name'] ?? null;
                    $lastName  = $result['last_name'] ?? null;
                    $username  = $result['username'] ?? null;
                    $name      = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));

                    echo $username.'n';

                    $account->update([
                        'username' => $username,
                        'name'     => $name,
                    ]);

                    $this->info("Updated {$account->telegram_id}: $name / $username");
                } else {
                        $this->warn("Could not fetch info for {$account->telegram_id}. Telegram said: " . json_encode($response->json()));
                }
            } catch (\Exception $e) {
                $this->error("Failed to update {$account->telegram_id}: " . $e->getMessage());
            }
        }

        $this->info("Done updating telegram accounts.");
    }
}
