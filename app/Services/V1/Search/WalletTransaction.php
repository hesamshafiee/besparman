<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\WalletTransaction as WalletTransactionModel;
use App\Models\Product;
use App\Models\User;

class WalletTransaction
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'status' => ['attribute_type' => 'attribute', 'in' => [WalletTransactionModel::STATUS_CONFIRMED, WalletTransactionModel::STATUS_PENDING, WalletTransactionModel::STATUS_REJECTED]],
                'type' => ['attribute_type' => 'attribute', 'in' => [
                    WalletTransactionModel::TYPE_DECREASE,
                    WalletTransactionModel::TYPE_INCREASE
                ]],
                'mobile' => ['index' => 'mobile', 'relation' => 'user'],
                'resnumber' => ['attribute_type' => 'attribute', 'like' => false],
                'user_id' => ['attribute_type' => 'attribute', 'like' => false],
                'user' => ['with' => 'user'],
                'userName' => ['index' => 'name', 'relation' => 'user'],
                'bank_name' => ['index' => 'bank_name', 'relation' => 'payment'],
                'bank_info' => ['index' => 'bank_info', 'relation' => 'payment'],
                'created_at' => ['attribute_type' => 'attribute'],
                'operator_id' => ['attribute_type' => 'attribute', 'like' => false],
                'detail_hide' => ['attribute_type' => 'hidden','index' => 'detail', 'in' =>
                    [
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_PRESENTER,
                        WalletTransactionModel::DETAIL_INCREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_DECREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_ESAJ,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE_BUYER,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE,
                        WalletTransactionModel::DETAIL_DECREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_REFUND,
                        WalletTransactionModel::DETAIL_DECREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_CARD,
                    ]
                ],
                'detail' => ['attribute_type' => 'attribute', 'in' =>
                    [
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_PRESENTER,
                        WalletTransactionModel::DETAIL_INCREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_DECREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_ESAJ,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE_BUYER,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE,
                        WalletTransactionModel::DETAIL_DECREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_REFUND,
                        WalletTransactionModel::DETAIL_DECREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_CARD,
                    ]
                ],
                'third_party_status' => ['attribute_type' => 'attribute', 'in' => [true, false]],
                'main_page' => ['attribute_type' => 'attribute', 'in' => [true, false]],
                'user_type' => ['attribute_type' => 'attribute', 'in' => [
                    User::TYPE_ORIDINARY,
                    User::TYPE_PANEL,
                    User::TYPE_WEBSERVICE,
                    User::TYPE_ADMIN,
                ]],
                'product_name' => ['attribute_type' => 'attribute'],
                'operator' => ['index' => 'name', 'relation' => 'operator', 'in' => [
                    Operator::IRANCELL,
                    Operator::APTEL,
                    Operator::SHATEL,
                    Operator::MCI,
                    Operator::RIGHTEL
                ]],
                'province' => ['attribute_type' => 'attribute'],
                'payment' => ['with' => 'payment'],
                'city' => ['attribute_type' => 'attribute'],
                'charged_mobile' => ['attribute_type' => 'attribute'],
                'order_id' => ['attribute_type' => 'attribute', 'like' => false],
                'sum' => ['attribute_type' => 'sum', 'value' => ['value', 'original_price', 'countAll']],
                'groupBy' => ['status', 'type', 'product_type' ,'operator_id' ,'province'  , 'city' , 'user_type', 'third_party_status', 'main_page', 'product_name', 'user_id', 'order_id', 'created_at', 'detail', 'provider']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'status' => ['attribute_type' => 'attribute', 'in' => [WalletTransactionModel::STATUS_CONFIRMED, WalletTransactionModel::STATUS_PENDING, WalletTransactionModel::STATUS_REJECTED]],
                'type' => ['attribute_type' => 'attribute', 'in' => [
                    WalletTransactionModel::TYPE_DECREASE,
                    WalletTransactionModel::TYPE_INCREASE
                ]],
                'created_at' => ['attribute_type' => 'attribute'],
                'detail_hide' => ['attribute_type' => 'hidden','index' => 'detail', 'in' =>
                    [
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_PRESENTER,
                        WalletTransactionModel::DETAIL_INCREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_DECREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_ESAJ,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE_BUYER,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE,
                        WalletTransactionModel::DETAIL_DECREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_REFUND,
                        WalletTransactionModel::DETAIL_DECREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_CARD,
                    ]
                ],
                'detail' => ['attribute_type' => 'attribute', 'in' =>
                    [
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_PRESENTER,
                        WalletTransactionModel::DETAIL_INCREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_DECREASE_TRANSFER,
                        WalletTransactionModel::DETAIL_INCREASE_PURCHASE_ESAJ,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE_BUYER,
                        WalletTransactionModel::DETAIL_DECREASE_PURCHASE,
                        WalletTransactionModel::DETAIL_DECREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_ADMIN,
                        WalletTransactionModel::DETAIL_INCREASE_REFUND,
                        WalletTransactionModel::DETAIL_DECREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_ONLINE,
                        WalletTransactionModel::DETAIL_INCREASE_CARD,
                    ]
                ],
                'charged_mobile' => ['attribute_type' => 'attribute'],
                'order_id' => ['attribute_type' => 'attribute', 'like' => false],
                'sum' => ['attribute_type' => 'sum', 'value' => ['value', 'original_price', 'countAll']],
                'groupBy' => ['status', 'type', 'product_type' ,'operator_id', 'product_name', 'created_at', 'order_id'],
                'city' => ['attribute_type' => 'attribute'],
                'mobile' => ['index' => 'mobile', 'relation' => 'user'],
                'resnumber' => ['attribute_type' => 'attribute'],
                'third_party_status' => ['attribute_type' => 'attribute', 'in' => [true, false]],
                'main_page' => ['attribute_type' => 'attribute', 'in' => [true, false]],
                'payment' => ['with' => 'payment'],
                'product_name' => ['attribute_type' => 'attribute'],
                'operator' => ['index' => 'name', 'relation' => 'operator', 'in' => [
                    Operator::IRANCELL,
                    Operator::APTEL,
                    Operator::SHATEL,
                    Operator::MCI,
                    Operator::RIGHTEL
                ]],
            ];
        }

        return [];
    }
}
