<?php

namespace App\Services\V1\Search;

use App\Models\Order as OrderModel;

class Order
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'status' => ['attribute_type' => 'attribute', 'in' => [OrderModel::STATUSUNPAID, OrderModel::STATUSCANCELED, OrderModel::STATUSPAID, OrderModel::STATUSRESERVED, OrderModel::STATUSRECEIVED, OrderModel::STATUSPOSTED, OrderModel::STATUSRESERVED]],
                'store' => ['attribute_type' => 'attribute', 'like' => false],
                'user' => ['with' => 'user'],
                'products' => ['with' => 'products'],
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'status' => ['attribute_type' => 'attribute', 'in' => [OrderModel::STATUSUNPAID, OrderModel::STATUSCANCELED, OrderModel::STATUSPAID, OrderModel::STATUSRESERVED, OrderModel::STATUSRECEIVED, OrderModel::STATUSPOSTED, OrderModel::STATUSRESERVED]],
                'id' => ['attribute_type' => 'attribute', 'like' => false],
                'store' => ['attribute_type' => 'attribute', 'like' => false],
                'products' => ['with' => 'products'],
            ];
        }

        return [];
    }
}
