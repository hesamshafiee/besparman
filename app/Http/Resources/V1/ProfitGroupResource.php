<?php

namespace App\Http\Resources\V1;

use App\Models\ProfitSplit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfitGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'profit_split_ids' => $this->profit_split_ids,
            'profit_splits' => ProfitSplit::find($this->profit_split_ids)
        ];
    }
}
