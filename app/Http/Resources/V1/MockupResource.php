<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class MockupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'category_id'    => $this->category_id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'canvas_width'   => $this->canvas_width,
            'canvas_height'  => $this->canvas_height,
            'dpi'            => $this->dpi,
            'print_x'        => $this->print_x,
            'print_y'        => $this->print_y,
            'print_width'    => $this->print_width,
            'print_height'   => $this->print_height,
            'print_rotation' => $this->print_rotation,
            'fit_mode'       => $this->fit_mode,
            'layers'         => $this->layers,      // cast به آرایه در مدل
            'preview_bg'     => $this->preview_bg,
            'is_active'      => (bool) $this->is_active,
            'sort'           => $this->sort,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
