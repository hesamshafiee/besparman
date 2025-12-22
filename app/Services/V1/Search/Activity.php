<?php

namespace App\Services\V1\Search;

use App\Models\Product as ProductModel;

class Activity
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'log_name' => ['attribute_type' => 'attribute'],
                'subject_type' => ['attribute_type' => 'attribute'],
                'event' => ['attribute_type' => 'attribute'],
                'causer_type' => ['attribute_type' => 'attribute'],
                'subject_id' => ['attribute_type' => 'attribute', 'like' => false],
                'causer_id' => ['attribute_type' => 'attribute', 'like' => false],
                'created_at' => ['attribute_type' => 'attribute'],
                'updated_at' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
