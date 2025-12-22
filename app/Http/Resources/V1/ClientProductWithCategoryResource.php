<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientProductWithCategoryResource extends JsonResource
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
            'description' => $this->description,
            'description_full' => $this->description_full,
            'price' => $this->price,
            'second_price' => $this->second_price,
            'images' => $this->images,
            'product_type' => $this->type,
            'sim_card_type' => $this->sim_card_type,
            'period' => $this->period,
            'categories_name' => $this->categories->pluck('name')
        ];
    }
}
