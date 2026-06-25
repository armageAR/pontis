<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendingRequests = $this->getPendingRequests($user);
        $notifications   = $this->getMembershipNotifications($user);

        return response()->json([
            'pending_requests'         => $pendingRequests,
            'membership_notifications' => $notifications,
        ]);
    }

    private function getPendingRequests($user): array
    {
        $query = DB::table('user_workshop')
            ->join('users', 'users.id', '=', 'user_workshop.user_id')
            ->join('workshops', 'workshops.id', '=', 'user_workshop.workshop_id')
            ->where('user_workshop.status', 'pending')
            ->whereNull('workshops.deleted_at')
            ->select([
                'user_workshop.user_id',
                'users.name as user_name',
                'users.email as user_email',
                'users.status as user_status',
                'user_workshop.workshop_id',
                'workshops.name as workshop_name',
                'workshops.number as workshop_number',
                'user_workshop.created_at as requested_at',
            ]);

        if (! $user->isSuperAdmin()) {
            $adminWorkshopIds = $user->workshops()
                ->wherePivot('role', 'admin')
                ->pluck('workshops.id');

            if ($adminWorkshopIds->isEmpty()) {
                return [];
            }

            $query->whereIn('user_workshop.workshop_id', $adminWorkshopIds);
        }

        return $query->orderBy('user_workshop.created_at', 'asc')->get()->toArray();
    }

    private function getMembershipNotifications($user): array
    {
        return DB::table('user_workshop')
            ->join('workshops', 'workshops.id', '=', 'user_workshop.workshop_id')
            ->where('user_workshop.user_id', $user->id)
            ->where('user_workshop.requested_by_user', true)
            ->whereIn('user_workshop.status', ['active', 'rejected'])
            ->whereNull('user_workshop.user_seen_at')
            ->whereNull('workshops.deleted_at')
            ->select([
                'user_workshop.workshop_id',
                'workshops.name as workshop_name',
                'workshops.number as workshop_number',
                'user_workshop.status',
                'user_workshop.updated_at as resolved_at',
            ])
            ->orderBy('user_workshop.updated_at', 'desc')
            ->get()
            ->toArray();
    }
}
