<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de "Mis Talleres" desde el perfil del Hermano: listado de membresías
 * activas y pendientes, y selección del Taller principal. El ingreso reutiliza
 * el endpoint de solicitud existente y la salida usa WorkshopController::leave.
 */
class ProfileWorkshopController extends Controller
{
    /**
     * Lista las membresías del usuario (activas y solicitudes pendientes) con
     * estado, rol, indicador de principal y datos del Taller para mostrar.
     * Usa workshopMemberships() (sin filtro de estado) para incluir pendientes.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $memberships = $user->workshopMemberships()
            ->whereIn('user_workshop.status', ['active', 'pending'])
            ->orderBy('workshops.number')
            ->get(['workshops.id', 'workshops.name', 'workshops.number', 'workshops.zone_name', 'workshops.city']);

        $data = $memberships->map(fn ($w) => [
            'id'                => $w->id,
            'name'              => $w->name,
            'number'            => $w->number,
            'zone_name'         => $w->zone_name,
            'city'              => $w->city,
            'status'            => $w->pivot->status,
            'my_role'           => $w->pivot->role,
            'is_principal'      => (bool) $w->pivot->is_principal,
            'requested_by_user' => (bool) $w->pivot->requested_by_user,
        ]);

        return response()->json($data);
    }

    /**
     * Marca un Taller activo como principal de forma atómica: quita el flag del
     * principal anterior y lo asigna al seleccionado. Rechaza membresías que no
     * estén activas (pendientes, rechazadas, inexistentes).
     */
    public function setPrincipal(Request $request, Workshop $workshop): JsonResponse
    {
        $user = $request->user();

        $membership = $user->workshopMemberships()
            ->where('workshop_id', $workshop->id)
            ->first();

        if (! $membership || $membership->pivot->status !== 'active') {
            return response()->json([
                'message' => 'Solo podés marcar como principal un taller donde tengas una membresía activa.',
            ], 422);
        }

        DB::transaction(function () use ($user, $workshop) {
            DB::table('user_workshop')
                ->where('user_id', $user->id)
                ->update(['is_principal' => false]);

            DB::table('user_workshop')
                ->where('user_id', $user->id)
                ->where('workshop_id', $workshop->id)
                ->update(['is_principal' => true]);
        });

        return response()->json(['message' => 'Taller principal actualizado.']);
    }
}
