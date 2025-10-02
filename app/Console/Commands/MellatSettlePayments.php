<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SoapClient;
use App\Models\Payment;

class MellatSettlePayments extends Command
{
    protected $signature = 'payments:settle-mellat';
    protected $description = 'Call bpSettleRequest for paid Mellat payments';

    public function handle()
    {
        $terminalId = env('MELLAT_TERMINAL_ID');
        $userName = env('MELLAT_USERNAME');
        $userPassword = env('MELLAT_PASSWORD');
        $from = now()->setTime(20, 0, 0);
        $to = now()->setTime(23, 15, 0);

        $client = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');

        $payments = Payment::where('bank_name', 'Mellat')
            ->where('status', 'paid')
            ->whereNotNull('bank_info')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No Mellat paid payments found.');
            return;
        }

        foreach ($payments as $payment) {
            $info = json_decode($payment->bank_info, true);

            $orderId = $info['SaleOrderId'] ?? null;
            $saleReferenceId = $info['SaleReferenceId'] ?? null;

            if (!$orderId || !$saleReferenceId) {
                $this->warn("Missing SaleOrderId or SaleReferenceId in payment ID {$payment->id}");
                continue;
            }

            try {
                $response = $client->bpSettleRequest([
                    'terminalId' => $terminalId,
                    'userName' => $userName,
                    'userPassword' => $userPassword,
                    'orderId' => $orderId,
                    'saleOrderId' => $orderId,
                    'saleReferenceId' => $saleReferenceId,
                ]);

                if ($response->return == 0 || $response->return == 45) {
                    $this->info("Settled payment ID {$payment->id}");
                } else {
                    $this->error("Failed to settle ID {$payment->id}, code: " . $response->return);
                }
            } catch (\Exception $e) {
                $this->error("SOAP error on payment ID {$payment->id}: " . $e->getMessage());
            }
        }

        $this->info('All eligible payments processed.');
    }
}
