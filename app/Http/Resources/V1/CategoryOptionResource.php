<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryOptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'option'       => new OptionResource($this->resource),
            'pivot'        => [
                'is_required' => is_null($this->pivot->is_required)
                    ? (bool)$this->is_required
                    : (bool)$this->pivot->is_required,
                'is_active'   => (bool)$this->pivot->is_active,
                'sort_order'  => (int)$this->pivot->sort_order,
            ],
        ];
    }
}
