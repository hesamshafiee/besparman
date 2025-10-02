<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\Product as ProductModel;

class PointHistory
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'type' => ['attribute_type' => 'attribute'],
                'product_name' => ['attribute_type' => 'attribute'],
                'point' => ['attribute_type' => 'attribute'],
                'user_id' => ['attribute_type' => 'attribute', 'like' => false],
                'product_id' => ['attribute_type' => 'attribute', 'like' => false],
                'operator_id' => ['attribute_type' => 'attribute', 'like' => false],
                'created_at' => ['attribute_type' => 'attribute'],
                'user' => ['with' => 'user']
            ];
        }

        return [];
    }
}
