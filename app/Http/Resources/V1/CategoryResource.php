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
            'status' => $this->status,
            'data' => $this->data,
            'default_setting' => $this->default_setting,
            'base_price' => $this->base_price,
            'markup_price' => $this->markup_price,
            'show_in_work' => $this->show_in_work,
            'parent_name' => optional($this->parent)->name,
            'data' => $this->data,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
