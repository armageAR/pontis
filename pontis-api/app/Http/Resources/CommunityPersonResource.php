<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload comunitario de un Hermano (mínimo dato).
 *
 * Whitelist explícita: aunque la consulta traiga más columnas, este resource
 * nunca emite email, teléfono, WhatsApp ni DNI. Los campos masónicos deben
 * venir ya filtrados por VisibilityPolicy (masonic_id ligado a identidad,
 * masonic_status al bloque "masonic") antes de serializar.
 */
class CommunityPersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'last_name'      => $this->last_name,
            'masonic_id'     => $this->masonic_id,
            'masonic_status' => $this->masonic_status,
            'province'       => $this->province,
            'locality'       => $this->locality,
            'country'        => $this->country,
            'profession'     => $this->profession,
            'role'           => $this->role,
            'status'         => $this->status,
            'anonymous'      => (bool) ($this->anonymous ?? false),
            'workshops'      => $this->workshops,
        ];
    }
}
