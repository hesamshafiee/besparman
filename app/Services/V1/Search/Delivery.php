<?php

namespace App\Services\V1\Search;

use App\Models\Product as ProductModel;

class Delivery
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'order_id' => ['attribute_type' => 'attribute']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'order_id' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
