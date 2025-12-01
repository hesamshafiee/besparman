<?php


namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class OptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'code'        => $this->code,
            'type'        => $this->type,
            'is_required' => (bool) $this->is_required,
            'is_active'   => (bool) $this->is_active,
            'meta'        => $this->meta ?? (object)[],
            'sort_order'  => $this->sort_order,
            'values_count'=> $this->whenCounted('values'),
            'values'      => OptionValueResource::collection($this->whenLoaded('values')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
