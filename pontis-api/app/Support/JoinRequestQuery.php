<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class JoinRequestQuery
{
    /**
     * Solicitudes de ingreso pendientes o con corrección solicitada dentro del
     * alcance del revisor: alcance global para Superadmin, y solo los Talleres
     * administrados para un Admin de Taller. Un usuario sin Talleres
     * administrados no ve ninguna solicitud.
     */
    public static function pendingForReviewer(User $user): Builder
    {
        $query = DB::table('user_workshop')
            ->join('users', 'users.id', '=', 'user_workshop.user_id')
            ->join('workshops', 'workshops.id', '=', 'user_workshop.workshop_id')
            ->whereIn('user_workshop.status', ['pending', 'correction_requested'])
            // Revisables: usuarios ya con email verificado (pending) o Hermanos
            // activos que piden ingresar a otro Taller. Se excluyen los que aún
            // no verificaron su email (verifying) y los estados terminales.
            ->whereIn('users.status', ['pending', 'active'])
            ->whereNull('workshops.deleted_at');

        if (! $user->isSuperAdmin()) {
            $adminWorkshopIds = $user->workshops()
                ->wherePivot('role', 'admin')
                ->pluck('workshops.id');

            // whereIn con colección vacía no devuelve filas.
            $query->whereIn('user_workshop.workshop_id', $adminWorkshopIds);
        }

        return $query;
    }
}
