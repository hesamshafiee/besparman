<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\WalletTransaction as WalletTransactionModel;
use App\Models\Product;
use App\Models\User;

class ReportDailyBalance
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'user_id' => ['attribute_type' => 'attribute', 'like' => false],
                'balance' => ['attribute_type' => 'attribute', 'like' => false],
                'date' => ['attribute_type' => 'attribute', 'like' => false],
                'sum' => ['attribute_type' => 'sum', 'value' => ['balance']],
                'groupBy' => ['user_id']

            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'balance' => ['attribute_type' => 'attribute', 'like' => false],
                'date' => ['attribute_type' => 'attribute', 'like' => false],
                'sum' => ['attribute_type' => 'sum', 'value' => ['balance']]
            ];
        }

        return [];
    }
}
