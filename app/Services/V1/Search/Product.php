<?php

namespace App\Services\V1\Search;

use App\Models\Product as ProductModel;

class Product
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'category' => ['index' => 'name', 'relation' => 'categories'],
                'categories' => ['with' => 'categories'],
                'status' => ['attribute_type' => 'attribute', 'in' => [ProductModel::STATUS_ACTIVE, ProductModel::STATUS_INACTIVE]],
                'deliverable' => ['attribute_type' => 'attribute', 'in' => [ProductModel::STATUS_ACTIVE, ProductModel::STATUS_INACTIVE]],
                
                'category_name' => ['attribute_type' => 'attribute'],
                'created_at' => ['attribute_type' => 'attribute'],
                'profile_id' => ['attribute_type' => 'attribute', 'like' => false],
                
                'period' => ['attribute_type' => 'attribute'],
                'name' => ['attribute_type' => 'attribute']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'category' => ['index' => 'id', 'relation' => 'categories'],
                'check' => ['status' => true],
                'categories' => ['with' => 'categories'],
                'status' => ['attribute_type' => 'attribute', 'in' => [ProductModel::STATUS_ACTIVE, ProductModel::STATUS_INACTIVE]],
                'deliverable' => ['attribute_type' => 'attribute', 'in' => [ProductModel::STATUS_ACTIVE, ProductModel::STATUS_INACTIVE]],
                'period' => ['attribute_type' => 'attribute'],
                'name' => ['attribute_type' => 'attribute'],
                'created_at' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
