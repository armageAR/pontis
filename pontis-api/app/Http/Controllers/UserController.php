<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search'         => ['nullable', 'string', 'max:255'],
            'role'           => ['nullable', 'string', 'in:superadmin,user'],
            'status'         => ['nullable', 'string', 'in:pending,active,rejected,suspended,inactive'],
            'workshop_id'    => ['nullable', 'integer', 'exists:workshops,id'],
            'workshop_role'  => ['nullable', 'string', 'in:admin,member'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'        => ['nullable', 'string', 'in:name,email,role,status,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $currentUser = $request->user();

        $query = User::with('workshops');

        if (! $currentUser->isSuperAdmin()) {
            $myWorkshopIds = $currentUser->workshops()->pluck('workshops.id');
            $query->whereHas('workshops', fn ($q) => $q->whereIn('workshops.id', $myWorkshopIds));
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('unaccent(LOWER(name)) like unaccent(?)', ["%{$search}%"])
                  ->orWhereRaw('unaccent(LOWER(email)) like unaccent(?)', ["%{$search}%"]);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('workshop_id')) {
            $query->whereHas('workshops', fn ($q) => $q->where('workshops.id', $request->input('workshop_id')));
        }

        if ($request->filled('workshop_role')) {
            $query->whereHas('workshops', fn ($q) => $q->where('user_workshop.role', $request->input('workshop_role')));
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);

        return UserResource::collection($query->paginate($perPage));
    }

    public function myWorkshops(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->isSuperAdmin()) {
            $myMemberships = $currentUser->workshops()->get(['workshops.id'])->keyBy('id');

            $workshops = \App\Models\Workshop::orderBy('number')->get(['id', 'name', 'number'])
                ->map(fn ($w) => [
                    'id'      => $w->id,
                    'name'    => $w->name,
                    'number'  => $w->number,
                    'my_role' => $myMemberships->get($w->id)?->pivot->role ?? null,
                ]);
        } else {
            $workshops = $currentUser->workshops()->orderBy('number')
                ->get(['workshops.id', 'workshops.name', 'workshops.number'])
                ->map(fn ($w) => [
                    'id'      => $w->id,
                    'name'    => $w->name,
                    'number'  => $w->number,
                    'my_role' => $w->pivot->role,
                ]);
        }

        return response()->json($workshops);
    }

    public function updateStatus(Request $request, User $user): UserResource|JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,active,rejected,suspended,inactive'],
        ]);

        $currentUser = $request->user();

        if (! $this->canActOn($currentUser, $user)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $newStatus = $request->input('status');
        $attrs = ['status' => $newStatus];

        // Al activar manualmente un usuario que aún no verificó su email,
        // se da por validado y se sella la fecha de verificación con hoy.
        if ($newStatus === 'active' && is_null($user->email_verified_at)) {
            $attrs['email_verified_at'] = now();
        }

        $user->update($attrs);

        return new UserResource($user->load('workshops'));
    }

    public function update(Request $request, User $user): UserResource|JsonResponse
    {
        $request->validate([
            'name'  => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'role'  => ['sometimes', 'required', 'string', 'in:superadmin,user'],
        ]);

        $currentUser = $request->user();
        $isSelf = $currentUser->id === $user->id;

        if ($isSelf && ! $currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'No podés editarte a vos mismo.'], 403);
        }

        if (! $isSelf && ! $this->canActOn($currentUser, $user)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($request->has('role') && ! $currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Solo un Super Admin puede cambiar el rol global.'], 403);
        }

        $user->update($request->only(['name', 'email', 'role']));

        return new UserResource($user->load('workshops'));
    }

    public function updatePassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $currentUser = $request->user();

        if (! $this->canActOn($currentUser, $user)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $user->update(['password' => $request->input('password')]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    public function addWorkshop(Request $request, User $user, Workshop $workshop): UserResource|JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'No podés modificarte a vos mismo.'], 403);
        }

        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdminOfWorkshop($workshop)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (! $user->workshops()->where('workshop_id', $workshop->id)->exists()) {
            $user->workshops()->attach($workshop->id, ['role' => 'member']);
        }

        return new UserResource($user->load('workshops'));
    }

    public function updateWorkshopRole(Request $request, User $user, Workshop $workshop): UserResource|JsonResponse
    {
        $data = $request->validate([
            'role'   => ['sometimes', 'required', 'string', 'in:admin,member'],
            'status' => ['sometimes', 'required', 'string', 'in:active,pending,rejected,correction_requested,inactive,suspended,ended,historical'],
        ]);

        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'No podés modificarte a vos mismo.'], 403);
        }

        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdminOfWorkshop($workshop)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $pivot = [];
        if (isset($data['role']))   $pivot['role']   = $data['role'];
        if (isset($data['status'])) $pivot['status'] = $data['status'];

        $user->workshops()->updateExistingPivot($workshop->id, $pivot);

        return new UserResource($user->load('workshops'));
    }

    public function removeWorkshop(Request $request, User $user, Workshop $workshop): UserResource|JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'No podés modificarte a vos mismo.'], 403);
        }

        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdminOfWorkshop($workshop)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $user->workshops()->detach($workshop->id);

        return new UserResource($user->load('workshops'));
    }

    private function canActOn(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        $actorAdminWorkshopIds = $actor->workshops()
            ->wherePivot('role', 'admin')
            ->pluck('workshops.id');

        if ($actorAdminWorkshopIds->isEmpty()) {
            return false;
        }

        return $target->workshops()
            ->whereIn('workshops.id', $actorAdminWorkshopIds)
            ->exists();
    }
}
