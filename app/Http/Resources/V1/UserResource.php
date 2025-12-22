<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'balance' => $this->wallet->value ?? 0,
            'type' => $this->type,
            'groupId' => $this->profitGroups?->first()?->id,
            'profile_id' => optional($this->profile)->id,
            'points' => $this->points,
            'two_step' => $this->two_step,
            'city' => optional($this->profile)->city,
            'province' => optional($this->profile)->province,
            'national_code' => optional($this->profile)->national_code,
            'legal_info' => json_decode(optional($this->profile)->legal_info),
            'private' => $this->private,
            'profile_confirm' => $this->profile_confirm,
            'last_login' => $this->last_login,
            'created_at' => $this->created_at
        ];
    }
}
