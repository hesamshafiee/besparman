<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'type' => $this->type,
            'resnumber' => $this->resnumber,
            'value' => $this->value,
            'status' => $this->status,
            'transfer_from_id' => $this->transfer_from_id,
            'transfer_to_id' => $this->transfer_to_id,
            'confirmed_by' => $this->confirmed_by,
            'description' => $this->description,
            'detail' => $this->detail,
            'extra_info' => $this->extra_info,
            'charged_mobile' => $this->charged_mobile,
            'wallet_value_after_transaction' => $this->wallet_value_after_transaction,
            'province' => $this->province,
            'city' => $this->city,
            'user_type' => $this->user_type,
            'product_type' => $this->product_type,
            'product_name' => $this->product_name,
            'order_id' => $this->order_id,
            'created_at' => $this->created_at,
            'user_id' => $this->user_id,
            'operator_id' => $this->operator_id,
            'third_party_status' => $this->third_party_status,
            'third_party_info' => $this->third_party_info,
            'webservice_code' => $this->webservice_code,
            'original_price' => $this->original_price,
            'provider' => $this->provider,
            'user' => $this->whenLoaded('user'),
            'payment' => $this->whenLoaded('payment')
        ];
    }
}
