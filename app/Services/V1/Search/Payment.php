<?php

namespace App\Services\V1\Search;

use App\Models\Payment as PaymentModel;

class Payment
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'status' => ['attribute_type' => 'attribute', 'in' => [PaymentModel::STATUSPAID, PaymentModel::STATUSCANCELED, PaymentModel::STATUSUNPAID, PaymentModel::STATUSREJECT, PaymentModel::BANKSTATEOK]],
                'status_hide' => ['attribute_type' => 'hidden','index' => 'status', 'in' => [PaymentModel::STATUSPAID, PaymentModel::STATUSCANCELED, PaymentModel::STATUSUNPAID, PaymentModel::STATUSREJECT, PaymentModel::BANKSTATEOK]],
                'user' => ['with' => 'user'],
                'sum' => ['attribute_type' => 'sum', 'value' => ['price']],
                'userName' => ['index' => 'name', 'relation' => 'user'],
                'groupBy' => ['status', 'bank_name', 'created_at'],
                'created_at' => ['attribute_type' => 'attribute'],
                'resnumber' => ['attribute_type' => 'attribute'],
                'bank_name' => ['attribute_type' => 'attribute'],
                'bank_info' => ['attribute_type' => 'attribute'],
                'price' => ['attribute_type' => 'attribute']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'status' => ['attribute_type' => 'attribute', 'in' => [PaymentModel::STATUSPAID, PaymentModel::STATUSCANCELED, PaymentModel::STATUSUNPAID, PaymentModel::STATUSREJECT, PaymentModel::BANKSTATEOK]],
                'sum' => ['attribute_type' => 'sum', 'value' => ['price']],
                'created_at' => ['attribute_type' => 'attribute'],
                'groupBy' => ['status', 'bank_name', 'created_at'],
                'resnumber' => ['attribute_type' => 'attribute'],
                'bank_name' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
