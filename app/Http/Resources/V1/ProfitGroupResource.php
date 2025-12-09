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
            'designer_profit' => $this->designer_profit,
            'site_profit' => $this->site_profit,
            'referrer_profit' => $this->referrer_profit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
