<?php

namespace App\Console\Commands;

use App\Models\Operator;
use App\Models\OperatorRemain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Notifications\SendTelegramMessage;

class OperatorRemaining extends Command
{
    protected $signature = 'report:operators-remaining';

    protected $description = 'Store and send operator remaining balances to Telegram';

    public function handle()
    {
        try {
            $reminingOfOperators = Operator::getRemaining();

            OperatorRemain::insert($reminingOfOperators);

            $format = fn($num) => $num !== null ? number_format($num, 0, '.', ',') : '❌';

            $message  = " گزارش موجودی اپراتورها 📊\n\n";
            $message .= " ایرانسل: " . $format($reminingOfOperators['irancell']) . "\n";
            $message .= " رایتل: " . $format($reminingOfOperators['rightel']) . "\n";
            $message .= " بسته‌های همراه اول (رادین): " . $format($reminingOfOperators['mciPackagesRadin']) . "\n";
            $message .= " شارژ همراه اول (رادین): " . $format($reminingOfOperators['mciChargeRadin']) . "\n";
            $message .= " آی‌گپ همراه اول: " . $format($reminingOfOperators['mciIgap']) . "\n";
            $message .= " کیف پول CC آپتل: " . $format($reminingOfOperators['aptelccwallet']) . "\n";
            $message .= " کیف پول CP آپتل: " . $format($reminingOfOperators['aptelcpwallet']) . "\n";
            $message .= " شاتل: " . $format($reminingOfOperators['shatel']) . "\n\n";

            (new SendTelegramMessage())->sendTelegramMessage($message,'8437404357:AAHN1824HNKHT5W3OVR_-JNe-TQJAP6bYM0');

        } catch (\Throwable $e) {
            Log::error('OperatorRemaining command error: ' . $e->getMessage());
        }

        $this->info('Operator remaining balance sent to Telegram successfully');
    }
}
