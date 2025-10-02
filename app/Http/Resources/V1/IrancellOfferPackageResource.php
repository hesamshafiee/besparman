<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IrancellOfferPackageResource extends JsonResource
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
            'mobile_number' => $this->mobile_number,
            'offerCode' => $this->offerCode,
            'name' => $this->name,
            'amount' => $this->amount,
            'offerType' => $this->offerType,
            'validityDays' => $this->validityDays,
            'offerDesc' => $this->offerDesc,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
