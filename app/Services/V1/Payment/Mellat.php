<?php

namespace App\Services\V1\Payment;

use App\Models\Payment;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Support\Facades\DB;
use SoapClient;
use SoapFault;

class Mellat implements Gateway
{
    protected $client;

    public function __construct()
    {
        $this->client = new SoapClient(env('MELLAT_WSDL', 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'), [
            'encoding' => 'UTF-8',
        ]);
    }

    public function pay(int $price, string $resnumber, string $returnUrl): array
    {
        $localDate = now()->format('Ymd');
        $localTime = now()->format('His');

        $params = [
            'terminalId' => env('MELLAT_TERMINAL_ID'),
            'userName' => env('MELLAT_USERNAME'),
            'userPassword' => env('MELLAT_PASSWORD'),
            'orderId' => $resnumber,
            'amount' => $price,
            'localDate' => $localDate,
            'localTime' => $localTime,
            'additionalData' => '',
            'callBackUrl' => $returnUrl,
            'payerId' => 0,
        ];

        try {
            $response = $this->client->__soapCall('bpPayRequest', [$params]);
            $res = explode(',', $response->return);

            if ($res[0] == '0') {
                return ['token' => $res[1]];
            } else {
                throw new \Exception('Mellat Error Code: ' . $res[0]);
            }
        } catch (SoapFault $e) {
            throw new \Exception('SOAP Error: ' . $e->getMessage());
        }
    }

    public function confirm(Payment $payment, array $info): bool
    {
        $payment->refnumber = $info['SaleReferenceId'] ?? null;
        $payment->bank_name = 'Mellat';
        $bankInfo = null;

        if ((int)($info['ResCode'] ?? -1) === 0) {
            $params = [
                'terminalId' => env('MELLAT_TERMINAL_ID'),
                'userName' => env('MELLAT_USERNAME'),
                'userPassword' => env('MELLAT_PASSWORD'),
                'orderId' => $info['SaleOrderId'],
                'saleOrderId' => $info['SaleOrderId'],
                'saleReferenceId' => $info['SaleReferenceId'] ?? 0,
            ];

            try {
                $verifyResponse = $this->client->__soapCall('bpVerifyRequest', [$params]);
                $resultCode = $verifyResponse->return;
                $bankInfo = json_encode(array_merge($info, ['verify_datail' => $verifyResponse]), true);

                $payment->bank_info = $bankInfo;

                if ((int)$resultCode === 0) {
                    $payment->status = Payment::STATUSPAID;
                    $payment->sign = $payment->sign();
                    $payment->confirmed_by = 'system';

                    return DB::transaction(function () use ($payment) {
                        if ($payment->save()) {
                            $response = Wallet::increaseByBank($payment);
                            if ($response['status']) {
                                $payment->transaction_id = $response['transaction_id'];
                                $payment->save();
                                return true;
                            }
                        }
                        return false;
                    });
                }
            } catch (SoapFault $e) {
                throw new \Exception('SOAP Error in confirm: ' . $e->getMessage());
            }
        }

        $payment->status = Payment::STATUSREJECT;
        if (isset($info['ResCode']) && $info['ResCode'] == 17) {
            $payment->status = Payment::STATUSCANCELED;
        }
        if (is_null($bankInfo)) {
            $payment->bank_info = json_encode($info, true);
        }
        $payment->save();

        return false;
    }

    public function reject(Payment $payment): bool
    {
        if ($payment->status !== Payment::STATUSPAID) {
            return false;
        }

        $params = [
            'terminalId' => env('MELLAT_TERMINAL_ID'),
            'userName' => env('MELLAT_USERNAME'),
            'userPassword' => env('MELLAT_PASSWORD'),
            'orderId' => $payment->resnumber,
            'saleOrderId' => $payment->order_id,
            'saleReferenceId' => $payment->refnumber,
        ];

        try {
            $reversalResponse = $this->client->__soapCall('bpReversalRequest', [$params]);
            $resultCode = $reversalResponse->return;
            $bankInfo = json_encode(['reversal_detail' => $reversalResponse], true);

            if ((int)$resultCode === 0) {
                $newPayment = new Payment();
                $newPayment->bank_info = $bankInfo;
                $newPayment->bank_name = 'Mellat';
                $newPayment->status = Payment::STATUSRETURNED;
                $newPayment->refnumber = $payment->refnumber;
                $newPayment->resnumber = now()->timestamp . mt_rand(1000, 9999);
                $newPayment->price = $payment->price;
                $newPayment->type = $payment->type;
                $newPayment->return_url = $payment->return_url;
                $newPayment->user_id = $payment->user_id;
                $newPayment->confirmed_by = 'system';
                $newPayment->order_id = $payment->order_id;

                return DB::transaction(function () use ($newPayment, $payment) {
                    if ($newPayment->save()) {
                        $newPayment->transaction_id = $payment->transaction_id;
                        $newPayment->sign = $newPayment->sign();
                        $newPayment->save();
                        return true;
                    }
                    return false;
                });
            }
        } catch (SoapFault $e) {
            throw new \Exception('SOAP Error in reject: ' . $e->getMessage());
        }

        return false;
    }
}
