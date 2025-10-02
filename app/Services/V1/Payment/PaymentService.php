<?php


namespace App\Services\V1\Payment;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

class PaymentService
{
    protected Gateway $gateway;

    /**
     * @param Gateway $gateway
     * @return void
     */
    public function setGateway(Gateway $gateway): void
    {
        $this->gateway = $gateway;
    }


    /**
     * @param int $value
     * @param string $type
     * @param string $mobile
     * @param string $bankName
     * @param string|null $returnUrl
     * @param int|null $orderId
     * @return mixed
     */
    public function increase(int $value, string $type, string $mobile = '', string $bankName = 'Saman', string $returnUrl = null, int $orderId = null): mixed
    {
        $setting = Setting::where('status', 1)->first();

        if ($setting) {
            $set = $setting->settings;
            if (lcfirst($bankName) === 'mellat' && isset($set['bank_mellat_status']) && !$set['bank_mellat_status']) {
                return false;
            }

            if (lcfirst($bankName) === 'saman' && isset($set['bank_saman_status']) && !$set['bank_saman_status']) {
                return false;
            }
        }

        $resnumber = substr(time() . mt_rand(100000, 999999) . mt_rand(1000, 9999), 0, 18);

        $payment = Payment::create([
            'resnumber' => $resnumber,
            'price' => $value,
            'type' => $type,
            'status' => Payment::STATUSUNPAID,
            'bank_name' => $bankName,
            'user_id' => User::getLoggedInUserOrGetFromGivenMobile($mobile)->id,
            'return_url' => $returnUrl,
            'order_id' => $orderId
        ]);

        $returnUrl = route('callback');
        $response = $this->gateway->pay($value, $resnumber, $returnUrl);

        if ($response) {
            return $response;
        } elseif ($payment) {
            return $payment;
        }

        return false;
    }

    /**
     * @param Payment $payment
     * @param array|null $info
     * @return mixed
     */
    public function confirm(Payment $payment, array $info = null): bool
    {
        return $this->gateway->confirm($payment, $info);
    }

    /**
     * @param Payment $payment
     * @return mixed
     */
    public function reject(Payment $payment): bool
    {
        return $this->gateway->reject($payment);
    }
}
