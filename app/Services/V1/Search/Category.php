<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\Product as ProductModel;

class Category
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'products' => ['with' => 'products'],
            ];
        } elseif ($type === 'filter-public') {
            return [
                'products' => ['with' => 'products'],
                
            ];
        }

        return [];
    }
}
