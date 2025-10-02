<?php

namespace App\Services\V1\Esaj;


use App\Models\Product;

interface Gateway
{
    public function topUp(
        string $mobileNumber,
        int $price,
        string $order_id,
        string $type,
        string $national_code,
        string $store_name,
        string $profile_id = '',
        string $ext_id = ''
    ): array;

    public function topUpPackage(
        string $mobileNumber,
        int $price,
        string $order_id,
        string $type,
        string $national_code,
        string $store_name,
        string $profile_id,
        string $ext_id = '',
        string $offerCode = '',
        string $offerType = ''
    ): array;

    public function packageList(): array;
    public function checkStatus(string $orderId): string;
    public function getRemaining(): array;
}
