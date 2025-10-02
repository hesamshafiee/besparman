<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\Product as ProductModel;

class PrizePurchase
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
                'user' => ['with' => 'user'],
                'prize' => ['with' => 'prize'],
                'prize_id' => ['attribute_type' => 'attribute', 'like' => false],
                'type' => ['index' => 'type', 'relation' => 'prize'],
                'created_at' => ['attribute_type' => 'attribute'],
                'groupBy' => ['prize_id']
            ];
        }

        return [];
    }
}
