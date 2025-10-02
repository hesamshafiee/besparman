<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\Product as ProductModel;

class PhoneBook
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'name' => ['attribute_type' => 'attribute'],
                'phone_number' => ['attribute_type' => 'attribute']
            ];
        } elseif ($type === 'filter') {
            return [
                'name' => ['attribute_type' => 'attribute'],
                'phone_number' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
