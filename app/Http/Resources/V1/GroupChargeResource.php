<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupChargeResource extends JsonResource
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
            'phone_numbers' => $this->phone_numbers,
            'phone_numbers_successful' => is_string($this->phone_numbers_successful)
                ? json_decode($this->phone_numbers_successful, true)
                : $this->phone_numbers_successful,
            'phone_numbers_unsuccessful' => is_string($this->phone_numbers_unsuccessful)
                ? json_decode($this->phone_numbers_unsuccessful, true)
                : $this->phone_numbers_unsuccessful,
            'status' => $this->status,
            'charge_status' => $this->charge_status,
            'topup_information' => $this->topup_information,
            'group_type' => $this->group_type,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'operator_id' => $this->operator_id,
            'user' => optional($this->user)->toArray(),
        ];
    }
}
