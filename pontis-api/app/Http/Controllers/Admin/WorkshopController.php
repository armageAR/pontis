<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkshopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignWorkshopUsersRequest;
use App\Http\Requests\Admin\RemoveWorkshopUsersRequest;
use App\Http\Requests\Admin\StoreWorkshopRequest;
use App\Http\Requests\Admin\UpdateWorkshopRequest;
use App\Http\Requests\Admin\WorkshopIndexRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WorkshopResource;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WorkshopController extends Controller
{
    public function index(WorkshopIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Workshop::class);

        $currentUser = $request->user();
        $query = Workshop::query();

        if ($request->boolean('my_workshops_only')) {
            $query->whereHas('users', fn ($q) => $q
                ->where('users.id', $currentUser->id)
                ->where('user_workshop.status', 'active')
            );
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(zone_name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(address) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(city) like ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('zone_number')) {
            $query->where('zone_number', $request->input('zone_number'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('work_day')) {
            $query->where('work_day', $request->input('work_day'));
        }

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->filled('province')) {
            $query->where('province', $request->input('province'));
        }

        $sortBy = $request->input('sort_by', 'number');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->input('per_page', 15);

        $paginated = $query->paginate($perPage);

        $allMemberships = $currentUser->workshopMemberships()->get(['workshops.id'])->keyBy('id');

        $paginated->getCollection()->each(function ($workshop) use ($allMemberships) {
            $m = $allMemberships->get($workshop->id);
            $workshop->is_member  = $m && $m->pivot->status === 'active';
            $workshop->is_pending = $m && $m->pivot->status === 'pending';
            $workshop->my_role    = ($m && $m->pivot->status === 'active') ? $m->pivot->role : null;
        });

        return WorkshopResource::collection($paginated);
    }

    public function join(Request $request, Workshop $workshop): WorkshopResource|JsonResponse
    {
        Gate::authorize('join', $workshop);

        $user = $request->user();

        $existing = $user->workshopMemberships()->where('workshop_id', $workshop->id)->first();

        if ($user->isSuperAdmin()) {
            // Superadmins join immediately, no approval needed
            if (! $existing) {
                $user->workshopMemberships()->attach($workshop->id, [
                    'role'   => 'member',
                    'status' => 'active',
                ]);
            }
            $workshop->is_member  = true;
            $workshop->is_pending = false;
            $workshop->my_role    = $existing?->pivot->role ?? 'member';
        } else {
            if (! $existing) {
                $user->workshopMemberships()->attach($workshop->id, [
                    'role'              => 'member',
                    'status'            => 'pending',
                    'requested_by_user' => true,
                ]);
            } elseif ($existing->pivot->status === 'rejected') {
                $user->workshopMemberships()->updateExistingPivot($workshop->id, [
                    'status'       => 'pending',
                    'user_seen_at' => null,
                ]);
            }

            $workshop->is_member  = false;
            $workshop->is_pending = true;
            $workshop->my_role    = null;
        }

        return new WorkshopResource($workshop);
    }

    public function leave(Request $request, Workshop $workshop): WorkshopResource|JsonResponse
    {
        Gate::authorize('leave', $workshop);

        $user = $request->user();
        $user->workshopMemberships()->detach($workshop->id);

        $workshop->is_member  = false;
        $workshop->is_pending = false;
        $workshop->my_role    = null;

        return new WorkshopResource($workshop);
    }

    public function approveJoinRequest(Request $request, Workshop $workshop, User $user): JsonResponse
    {
        Gate::authorize('approveMember', $workshop);

        $user->workshopMemberships()->updateExistingPivot($workshop->id, [
            'status'       => 'active',
            'user_seen_at' => null,
        ]);

        return response()->json(['message' => 'Solicitud aprobada.']);
    }

    public function rejectJoinRequest(Request $request, Workshop $workshop, User $user): JsonResponse
    {
        Gate::authorize('approveMember', $workshop);

        $user->workshopMemberships()->updateExistingPivot($workshop->id, [
            'status'       => 'rejected',
            'user_seen_at' => null,
        ]);

        return response()->json(['message' => 'Solicitud rechazada.']);
    }

    public function dismissNotification(Request $request, Workshop $workshop): JsonResponse
    {
        $user = $request->user();

        $membership = $user->workshopMemberships()
            ->where('workshop_id', $workshop->id)
            ->wherePivot('requested_by_user', true)
            ->first();

        if (! $membership) {
            return response()->json(null, 404);
        }

        if ($membership->pivot->status === 'active') {
            $user->workshopMemberships()->updateExistingPivot($workshop->id, ['user_seen_at' => now()]);
        } elseif ($membership->pivot->status === 'rejected') {
            $user->workshopMemberships()->detach($workshop->id);
        }

        return response()->json(['message' => 'Notificación descartada.']);
    }

    public function show(Workshop $workshop): WorkshopResource
    {
        Gate::authorize('view', $workshop);

        if (request()->has('include') && str_contains(request()->input('include'), 'users')) {
            $workshop->load('users');
        }

        return new WorkshopResource($workshop);
    }

    public function store(StoreWorkshopRequest $request): JsonResponse
    {
        Gate::authorize('create', Workshop::class);

        $workshop = Workshop::create($request->validated());

        return (new WorkshopResource($workshop))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateWorkshopRequest $request, Workshop $workshop): WorkshopResource
    {
        Gate::authorize('update', $workshop);

        $workshop->update($request->validated());

        return new WorkshopResource($workshop);
    }

    public function destroy(Workshop $workshop): JsonResponse
    {
        Gate::authorize('delete', $workshop);

        $workshop->delete();

        return response()->json(null, 204);
    }

    public function disable(Workshop $workshop): WorkshopResource
    {
        Gate::authorize('disable', $workshop);

        $workshop->update(['status' => WorkshopStatus::DISABLED]);

        return new WorkshopResource($workshop);
    }

    public function enable(Workshop $workshop): WorkshopResource
    {
        Gate::authorize('enable', $workshop);

        $workshop->update(['status' => WorkshopStatus::ACTIVE]);

        return new WorkshopResource($workshop);
    }

    public function users(Workshop $workshop): AnonymousResourceCollection
    {
        Gate::authorize('viewUsers', $workshop);

        $query = $workshop->users()->withPivot('role');

        if (request()->filled('search')) {
            $search = mb_strtolower(request()->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(users.name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(users.email) like ?', ["%{$search}%"]);
            });
        }

        if (request()->filled('workshop_role')) {
            $query->wherePivot('role', request()->input('workshop_role'));
        }

        $perPage = request()->input('per_page', 15);

        return UserResource::collection($query->paginate($perPage));
    }

    public function assignUsers(AssignWorkshopUsersRequest $request, Workshop $workshop): WorkshopResource
    {
        Gate::authorize('assignUsers', $workshop);

        $role = $request->input('role', 'member');

        foreach ($request->input('user_ids') as $userId) {
            if (! $workshop->users()->where('user_id', $userId)->exists()) {
                $workshop->users()->attach($userId, ['role' => $role]);
            }
        }

        if ($request->has('include') && str_contains($request->input('include', ''), 'users')) {
            $workshop->load('users');
        }

        return new WorkshopResource($workshop);
    }

    public function removeUsers(RemoveWorkshopUsersRequest $request, Workshop $workshop): WorkshopResource
    {
        Gate::authorize('removeUsers', $workshop);

        $workshop->users()->detach($request->input('user_ids'));

        return new WorkshopResource($workshop);
    }
}
