<?php

namespace App\Services\V1\Search;

use App\Models\User as UserModel;


class User
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'mobile' => ['attribute_type' => 'attribute'],
                'id' => ['attribute_type' => 'attribute', 'like' => false],
                'name' => ['attribute_type' => 'attribute'],
                'type' => ['attribute_type' => 'attribute', 'in' =>
                    [
                        UserModel::TYPE_ESAJ,
                        UserModel::TYPE_ORIDINARY,
                        UserModel::TYPE_PANEL,
                        UserModel::TYPE_WEBSERVICE,
                        UserModel::TYPE_ADMIN
                    ]
                ],
                'province' => ['index' => 'province', 'relation' => 'profile'],
                'city' => ['index' => 'city', 'relation' => 'profile'],
                'national_code' => ['index' => 'national_code', 'relation' => 'profile'],
                'profile_confirm' => ['attribute_type' => 'attribute'],
                'profile_id' => ['attribute_type' => 'attribute', 'like' => false],
                'created_at' => ['attribute_type' => 'attribute']
            ];
        }

        return [];
    }
}
