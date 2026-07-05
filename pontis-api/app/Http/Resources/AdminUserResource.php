<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload administrativo de usuario. Incluye datos operativos (email, estado
 * de verificación) que solo corresponden a flujos de administración.
 * Los endpoints comunitarios nunca deben usar este resource: para la
 * comunidad existe CommunityPersonResource y los payloads de VisibilityPolicy.
 */
class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'province'          => $this->province,
            'role'              => $this->role,
            'status'            => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'workshops'         => $this->whenLoaded('workshops', fn () => $this->workshops->map(fn ($w) => [
                'id'           => $w->id,
                'name'         => $w->name,
                'number'       => $w->number,
                'workshop_role' => $w->pivot->role ?? 'member',
            ])),
        ];
    }
}
