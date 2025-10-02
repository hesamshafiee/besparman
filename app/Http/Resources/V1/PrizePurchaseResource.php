<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrizePurchaseResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prize_id' => $this->prize_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'prize' => $this->prize,
            'third_party_info' => $this->third_party_info,
            'price' => $this->price,
            'points' => $this->points,
            'code' => $this->code,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user')
        ];
    }
}
