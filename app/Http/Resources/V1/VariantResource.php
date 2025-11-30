<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'category_id' => $this->category_id,
            'sku'         => $this->sku,
            'stock'       => (int) $this->stock,
            'add_price'   => (float) $this->add_price,
            'is_active'   => (bool) $this->is_active,

            'option_value_ids' => $this->whenLoaded('optionValues', function () {
                return $this->optionValues->pluck('id');
            }),

            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
