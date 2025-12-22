<?php

namespace App\Services\V1\Search;

use App\Models\Category as CategoryModel;

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
                'status' => ['attribute_type' => 'attribute', 'in' => [0, 1]],
                'name' => ['attribute_type' => 'attribute'],
                'parent_id' => ['attribute_type' => 'attribute', 'like' => false],
                'products' => ['with' => 'products'],
                'children' => ['with' => 'children'],
                'parent' => ['with' => 'parent'],
                'variants' => ['with' => 'variants'],
                'created_at' => ['attribute_type' => 'attribute'],
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['status' => true],
                'status' => ['attribute_type' => 'attribute', 'in' => [0, 1]],
                'name' => ['attribute_type' => 'attribute'],
                'parent_id' => ['attribute_type' => 'attribute', 'like' => false],
                'products' => ['with' => 'products'],
                'children' => ['with' => 'children'],
                'parent' => ['with' => 'parent'],
                'variants' => ['with' => 'variants'],
                'created_at' => ['attribute_type' => 'attribute'],
            ];
        }

        return [];
    }
}
