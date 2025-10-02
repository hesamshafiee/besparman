<?php

namespace App\Services\V1\Search;


class IrancellOfferPackage
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'mobile_number' => ['attribute_type' => 'attribute', 'like' => true],
                'offerCode' => ['attribute_type' => 'attribute', 'like' => true],
                'name' => ['attribute_type' => 'attribute', 'like' => true],
                'amount' => ['attribute_type' => 'attribute', 'like' => false],
                'offerType' => ['attribute_type' => 'attribute', 'like' => false],
                'validityDays' => ['attribute_type' => 'attribute', 'like' => false],
                'offerDesc' => ['attribute_type' => 'attribute', 'like' => false],
            ];
        }

        return [];
    }
}
