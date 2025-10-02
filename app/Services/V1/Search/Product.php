<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
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
                'type' => ['attribute_type' => 'attribute', 'in' => [
                    ProductModel::TYPE_CELL_INTERNET_PACKAGE,
                    ProductModel::TYPE_TD_LTE_INTERNET_PACKAGE,
                    ProductModel::TYPE_CELL_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_AMAZING_DIRECT_CHARGE,
                    ProductModel::TYPE_CART,
                    ProductModel::TYPE_CARD_CHARGE
                ]],
                'operator' => ['index' => 'name', 'relation' => 'operator', 'in' => [
                    Operator::IRANCELL,
                    Operator::APTEL,
                    Operator::SHATEL,
                    Operator::MCI,
                    Operator::RIGHTEL
                ]],
                'category_name' => ['attribute_type' => 'attribute'],
                'created_at' => ['attribute_type' => 'attribute'],
                'profile_id' => ['attribute_type' => 'attribute', 'like' => false],
                'sim_card_type' => ['attribute_type' => 'attribute', 'in' => [
                    ProductModel::SIM_CARD_TYPE_CREDIT,
                    ProductModel::SIM_CARD_TYPE_PERMANENT
                ]],
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
                'type' => ['attribute_type' => 'attribute', 'in' => [
                    ProductModel::TYPE_CELL_INTERNET_PACKAGE,
                    ProductModel::TYPE_TD_LTE_INTERNET_PACKAGE,
                    ProductModel::TYPE_CELL_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_AMAZING_DIRECT_CHARGE,
                    ProductModel::TYPE_CART,
                    ProductModel::TYPE_CARD_CHARGE
                ]],
                'operator' => ['index' => 'name', 'relation' => 'operator', 'in' => [
                    Operator::IRANCELL,
                    Operator::APTEL,
                    Operator::SHATEL,
                    Operator::MCI,
                    Operator::RIGHTEL
                ]],
                'sim_card_type' => ['attribute_type' => 'attribute', 'in' => [
                    ProductModel::SIM_CARD_TYPE_CREDIT,
                    ProductModel::SIM_CARD_TYPE_PERMANENT
                ]],
                'period' => ['attribute_type' => 'attribute'],
                'name' => ['attribute_type' => 'attribute'],
                'created_at' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
