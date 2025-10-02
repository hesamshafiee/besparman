<?php

namespace App\Services\V1\Search;

use App\Models\GroupCharge as GroupChargeModel;

class GroupCharge
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'status' => ['attribute_type' => 'attribute', 'in' => [GroupChargeModel::STATUS_PENDING, GroupChargeModel::STATUS_FINISHED, GroupChargeModel::STATUS_CANCELED]],
                'charge_status' => ['attribute_type' => 'attribute', 'in' => [GroupChargeModel::CHARGE_STATUS_PENDING, GroupChargeModel::CHARGE_STATUS_DOING, GroupChargeModel::CHARGE_STATUS_DONE, GroupChargeModel::CHARGE_STATUS_CANCELED]],
                'group_type' => ['attribute_type' => 'attribute', 'in' => [
                    GroupChargeModel::TYPE_TOPUP,
                    GroupChargeModel::TYPE_TOPUP_PACKAGE
                ]],
                'user' => ['with' => 'user'],
                'userName' => ['index' => 'name', 'relation' => 'user'],
                'userMobile' => ['index' => 'mobile', 'relation' => 'user'],
                'operator_id' => ['attribute_type' => 'attribute', 'like' => false],
                'groupBy' => ['status', 'group_type', 'created_at'],
                'created_at' => ['attribute_type' => 'attribute']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'status' => ['attribute_type' => 'attribute', 'in' => [GroupChargeModel::STATUS_PENDING, GroupChargeModel::STATUS_FINISHED, GroupChargeModel::STATUS_CANCELED]],
                'charge_status' => ['attribute_type' => 'attribute', 'in' => [GroupChargeModel::CHARGE_STATUS_PENDING, GroupChargeModel::CHARGE_STATUS_DOING, GroupChargeModel::CHARGE_STATUS_DONE, GroupChargeModel::CHARGE_STATUS_CANCELED]],
                'group_type' => ['attribute_type' => 'attribute', 'in' => [
                    GroupChargeModel::TYPE_TOPUP,
                    GroupChargeModel::TYPE_TOPUP_PACKAGE
                ]],
                'created_at' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
