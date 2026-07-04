<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\UserDegree;
use App\Models\UserPosition;
use App\Support\JoinRequestQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $this->getMembershipNotifications($user);

        return response()->json([
            'membership_notifications' => $notifications,
            'is_workshop_admin'        => $user->isAdminOfAnyWorkshop(),
            'pending_validation_count' => $this->getPendingValidationCount($user),
        ]);
    }

    /**
     * Cantidad de grados y cargos declarados y cambios de datos sensibles
     * pendientes de revisión que el actor puede resolver desde Administración →
     * Validaciones: alcance global para Superadmin, y solo los Talleres
     * administrados para un Admin de Taller (mismo alcance que las listas de
     * validaciones y trámites pendientes).
     */
    private function getPendingValidationCount($user): int
    {
        $degreeQuery = UserDegree::where('validation_status', 'declared');
        $positionQuery = UserPosition::where('validation_status', 'declared');
        $changeRequestQuery = ChangeRequest::where('status', 'pending');

        if (! $user->isSuperAdmin()) {
            $adminWorkshopIds = $user->workshops()
                ->wherePivot('role', 'admin')
                ->pluck('workshops.id');

            if ($adminWorkshopIds->isEmpty()) {
                return 0;
            }

            $degreeQuery->whereIn('workshop_id', $adminWorkshopIds);
            $positionQuery->whereIn('workshop_id', $adminWorkshopIds);
            $changeRequestQuery->whereHas('user.workshops', fn ($q) => $q->whereIn('workshops.id', $adminWorkshopIds));
        }

        return $degreeQuery->count()
            + $positionQuery->count()
            + $changeRequestQuery->count()
            + JoinRequestQuery::pendingForReviewer($user)->count();
    }

    private function getMembershipNotifications($user): array
    {
        return DB::table('user_workshop')
            ->join('workshops', 'workshops.id', '=', 'user_workshop.workshop_id')
            ->where('user_workshop.user_id', $user->id)
            ->where('user_workshop.requested_by_user', true)
            ->whereIn('user_workshop.status', ['active', 'rejected', 'correction_requested'])
            ->whereNull('user_workshop.user_seen_at')
            ->whereNull('workshops.deleted_at')
            ->select([
                'user_workshop.workshop_id',
                'workshops.name as workshop_name',
                'workshops.number as workshop_number',
                'user_workshop.status',
                'user_workshop.correction_notes',
                'user_workshop.updated_at as resolved_at',
            ])
            ->orderBy('user_workshop.updated_at', 'desc')
            ->get()
            ->toArray();
    }
}
