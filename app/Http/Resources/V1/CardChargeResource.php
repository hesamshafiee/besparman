<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'operator_id' => $this->operator_id,
            'serial' => $this->serial,
            'pin' => $this->pin,
            'price' => $this->price,
            'profit' => $this->profit,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'file_status' => $this->file_status,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'saled_at' => $this->saled_at,
            'order_count' => $this->order_count ?? 0, 
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
