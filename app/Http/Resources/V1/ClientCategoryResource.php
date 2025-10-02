<?php

namespace App\Http\Resources\V1;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'data' => $this->data,
            'products' => ClientProductResource::collection($this->whenLoaded('products', function () {
                return $this->products->where('status', Product::STATUS_ACTIVE);
            })),
        ];
    }
}
