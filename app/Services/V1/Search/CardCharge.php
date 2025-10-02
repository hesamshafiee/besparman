<?php

namespace App\Services\V1\Search;

use App\Models\GroupCharge as GroupChargeModel;

class CardCharge
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'groupBy' => ['created_at', 'order_id'],
                'order_id' => ['attribute_type' => 'attribute', 'like' => false],
                'operator_id' => ['attribute_type' => 'attribute', 'like' => false],
                'product_id' => ['attribute_type' => 'attribute', 'like' => false],
                'serial' => ['attribute_type' => 'attribute', 'like' => false],
                'created_at' => ['attribute_type' => 'attribute']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'groupBy' => ['created_at', 'order_id'],
                'order_id' => ['attribute_type' => 'attribute', 'like' => false],
                'operator_id' => ['attribute_type' => 'attribute', 'like' => false],
                'product_id' => ['attribute_type' => 'attribute', 'like' => false],
                'serial' => ['attribute_type' => 'attribute', 'like' => false],
                'created_at' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
