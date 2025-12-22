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
            'parent_id' => $this->parent_id,
            'status' => $this->status,
            'data' => $this->data,
            'default_setting' => $this->default_setting,
            'base_price' => $this->base_price,
            'markup_price' => $this->markup_price,
            'parent_name' => optional($this->parent)->name,
            'data' => $this->data,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
