<?php

namespace App\Services\V1\Search;

use App\Models\Operator;
use App\Models\Product as ProductModel;

class Ticket
{
    /**
     * @param string $type
     * @return array
     */
    public static function get(string $type): array
    {
        if ($type === 'filter') {
            return [
                'status' => ['attribute_type' => 'attribute', 'in' => [\App\Models\Ticket::STATUS_ANSWERED, \App\Models\Ticket::STATUS_ANSWERING, \App\Models\Ticket::STATUS_CLOSED]],
                'user' => ['with' => 'user'],
                'userName' => ['index' => 'name', 'relation' => 'user'],
                'userMobile' => ['index' => 'mobile', 'relation' => 'user'],
                'user_id' => ['attribute_type' => 'attribute', 'like' => false],
                'sum' => ['attribute_type' => 'sum', 'value' => ['countAll']],
                'groupBy' => ['status', 'user_id']
            ];
        } elseif ($type === 'filter-public') {
            return [
                'check' => ['auth' => true, 'user' => true],
                'status' => ['attribute_type' => 'attribute', 'in' => [\App\Models\Ticket::STATUS_ANSWERED, \App\Models\Ticket::STATUS_ANSWERING, \App\Models\Ticket::STATUS_CLOSED]],
                'groupBy' => ['status']
            ];
        }

        return [];
    }
}
