<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'category_name' => $this->category_name,
            'description' => $this->description,
            'description_full' => $this->description_full,
            'price' => $this->price,
            'second_price' => $this->second_price,
            'showable_price' => $this->showable_price,
            'images' => $this->images,
            'product_type' => $this->type,
            'operator_id' => $this->operator_id,
            'sim_card_type' => $this->sim_card_type,
            'period' => $this->period,
            'resnumber' => $this->resnumber,
            'pivot_address' => optional($this->pivot)->address,
            'order' => $this->order,
            'categories' => CategoryWithoutProductResource::collection($this->whenLoaded('categories')),
        ];
    }
}
