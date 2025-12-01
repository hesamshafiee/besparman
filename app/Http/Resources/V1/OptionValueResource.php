<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class OptionValueResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'option_id'     => $this->option_id,
            'name'          => $this->name,
            'code'          => $this->code,
            'is_active'     => (bool) $this->is_active,
            'meta'          => $this->meta ?? (object)[],
            'sort_order'    => $this->sort_order,
            'price_modifier'=> $this->price_modifier,
            'weight_modifier'=> $this->weight_modifier,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}