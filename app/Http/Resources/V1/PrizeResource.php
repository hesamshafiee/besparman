<?php

namespace App\Http\Resources\V1;

use App\Models\PrizeItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrizeResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $array = parent::toArray($request);
        $array['tags'] = $this->tags;
        $array['items_count'] = optional($this->prizeItems)->count();
        $array['items_used_count'] = optional($this->prizeItems->where('used', PrizeItem::USED_TRUE))->count();
        return $array;
    }
}
