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
                'type' => ['index' => 'type', 'relation' => 'products', 'in' => [
                    ProductModel::TYPE_CELL_INTERNET_PACKAGE,
                    ProductModel::TYPE_TD_LTE_INTERNET_PACKAGE,
                    ProductModel::TYPE_CELL_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_AMAZING_DIRECT_CHARGE,
                    ProductModel::TYPE_CART
                ]],
                'operator' => ['index' => 'name', 'relation' => 'products', 'relation2' => 'operator', 'in' => [
                    Operator::IRANCELL,
                    Operator::APTEL,
                    Operator::SHATEL,
                    Operator::MCI,
                    Operator::RIGHTEL
                ]],
                'sim_card_type' => ['index' => 'sim_card_type', 'relation' => 'products', 'in' => [
                    ProductModel::SIM_CARD_TYPE_CREDIT,
                    ProductModel::SIM_CARD_TYPE_PERMANENT
                ]],
                'period' => ['index' => 'period', 'relation' => 'products']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'products' => ['with' => 'products'],
                'type' => ['index' => 'type', 'relation' => 'products', 'in' => [
                    ProductModel::TYPE_CELL_INTERNET_PACKAGE,
                    ProductModel::TYPE_TD_LTE_INTERNET_PACKAGE,
                    ProductModel::TYPE_CELL_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                    ProductModel::TYPE_CELL_AMAZING_DIRECT_CHARGE,
                    ProductModel::TYPE_CART
                ]],
                'operator' => ['index' => 'name', 'relation' => 'products', 'relation2' => 'operator', 'in' => [
                    Operator::IRANCELL,
                    Operator::APTEL,
                    Operator::SHATEL,
                    Operator::MCI,
                    Operator::RIGHTEL
                ]],
                'sim_card_type' => ['index' => 'sim_card_type', 'relation' => 'products', 'in' => [
                    ProductModel::SIM_CARD_TYPE_CREDIT,
                    ProductModel::SIM_CARD_TYPE_PERMANENT
                ]],
                'period' => ['index' => 'period', 'relation' => 'products']
            ];
        }

        return [];
    }
}
