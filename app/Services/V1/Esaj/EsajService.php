<?php

namespace App\Services\V1\Esaj;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class EsajService
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
     * @param string $mobileNumber
     * @param int $price
     * @param string $order_id
     * @param string $type
     * @param string $national_code
     * @param string $store_name
     * @param string $profile_id
     * @param string $ext_id
     * @param bool $fakeResponse
     * @return array
     */
    public function topUp(string $mobileNumber, int $price, string $order_id, string $type, string $national_code, string $store_name, string $profile_id = '', string $ext_id = '', bool $fakeResponse = false): array
    {
        if (env('APP_ENV') === 'production') {
            return $this->gateway->topUp($mobileNumber, $price, $order_id, $type, $national_code, $store_name, $profile_id, $ext_id);
        }

        return ['status' => $fakeResponse];
    }

    /**
     * @param string $mobileNumber
     * @param int $price
     * @param string $order_id
     * @param string $type
     * @param string $national_code
     * @param string $store_name
     * @param string $profile_id
     * @param string $ext_id
     * @param string $offerCode
     * @param string $offerType
     * @param bool $fakeResponse
     * @return array
     */
    public function topUpPackage(string $mobileNumber, int $price, string $order_id, string $type, string $national_code, string $store_name, string $profile_id, string $ext_id = '', string $offerCode = '', string $offerType = '', bool $fakeResponse = false): array
    {
        if (env('APP_ENV') === 'production') {
            return $this->gateway->topUpPackage($mobileNumber, $price, $order_id, $type, $national_code, $store_name, $profile_id, $ext_id, $offerCode, $offerType);
        }

        return ['status' => $fakeResponse];
    }

    /**
     * @param string $mobile
     * @return array
     */
    public function getBillInquiry(string $mobile): array
    {
        return $this->gateway->getBillInquiry($mobile);
    }

    /**
     * @param string $mobile
     * @return array
     */
    public function getOfferPackage(string $mobile): array
    {
        return $this->gateway->getOfferPackage($mobile);
    }

    /**
     * @param string $mobile
     * @return array
     */
    public function getSimType(string $mobile): array
    {
        return $this->gateway->getSimType($mobile);
    }

    /**
     * @return array
     */
    public function packageList(): array
    {
        return $this->gateway->packageList();
    }

    /**
     * @param string $orderId
     * @return string
     */
    public function checkStatus(string $orderId): string
    {
        return $this->gateway->checkStatus($orderId);
    }
}
