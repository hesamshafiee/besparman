<?php

namespace App\Services\V1\Wallet;

interface Builder
{
    public function execute(array $data): array;
}
