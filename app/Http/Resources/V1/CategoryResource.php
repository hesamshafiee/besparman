<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'parent_name' => optional($this->parent)->name,
            'data' => $this->data,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
