<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\WalletTransaction as WalletTransactionModel;
use App\Models\Product;
use App\Models\User;

class WalletTransactionExtra
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'created_at' => ['attribute_type' => 'attribute'],
                'third_party_status' => ['attribute_type' => 'attribute', 'in' => [true, false]],
                'sum' => ['attribute_type' => 'sum', 'value' => ['value', 'taken_value']],
                'groupBy' => ['created_at']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'third_party_status' => ['attribute_type' => 'attribute', 'in' => [true, false]],
                'sum' => ['attribute_type' => 'sum', 'value' => ['value', 'taken_value']],
                'created_at' => ['attribute_type' => 'attribute'],
                'groupBy' => ['created_at']
            ];
        }

        return [];
    }
}
