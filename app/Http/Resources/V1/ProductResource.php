<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $disk = config('uploads.disk', 'public');

        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'variant_id' => $this->variant_id,
            'work_id'     => $this->work_id,

            'name'        => $this->name,
            'slug'        => $this->slug,
            'name_en'     => $this->name_en,

            'description'      => $this->description,
            'description_full' => $this->description_full,

            'sku'         => $this->sku,
            'price'       => $this->price,
            'currency'    => $this->currency,
            'type'        => $this->type,

            'minimum_sale'=> $this->minimum_sale,
            'dimension'   => $this->dimension,
            'score'       => $this->score,
            'status'      => $this->status,
            'sort'        => $this->sort,

            'original_path' => $this->original_path,
            'original_url'  => $this->original_path ? Storage::disk($disk)->url($this->original_path) : null,
            'preview_path'  => $this->preview_path,
            'preview_url'   => $this->preview_path ? Storage::disk($disk)->url($this->preview_path) : null,

            'settings'    => $this->settings,
            'options'     => $this->options,
            'meta'        => $this->meta,

            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
