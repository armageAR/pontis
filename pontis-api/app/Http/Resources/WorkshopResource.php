<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zone_number' => $this->zone_number,
            'zone_name' => $this->zone_name,
            'name' => $this->name,
            'number' => $this->number,
            'work_day' => $this->work_day,
            'work_frequency' => $this->work_frequency,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'country' => $this->country,
            'language' => $this->language,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'is_member'  => $this->is_member ?? false,
            'is_pending' => $this->is_pending ?? false,
            'my_role'    => $this->my_role ?? null,
        ];
    }
}
