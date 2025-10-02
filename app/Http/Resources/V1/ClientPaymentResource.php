<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return[
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'user_id' => $this->user_id,
            'price' => $this->price,
            'resnumber' => $this->resnumber,
            'type' => $this->type,
            'status' => $this->status,
            'bank_name' => $this->bank_name,
            'created_at' => $this->created_at,
            'bank_info' => $this->bank_info,
        ];
    }
}
