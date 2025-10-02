<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientCardChargeResource extends JsonResource
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
            'status' => $this->status,
            'product_id' => $this->product_id,
            'saled_at' => $this->saled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
