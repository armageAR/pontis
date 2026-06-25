<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'in:superadmin,admin,user'],
            'status' => ['nullable', 'string', 'in:pending,active,rejected,suspended,inactive'],
            'workshop_id' => ['nullable', 'integer', 'exists:workshops,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:name,email,role,status,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $currentUser = $request->user();

        $query = User::with('workshops');

        if ($currentUser->isSuperAdmin()) {
            // ve todos
        } else {
            $workshopIds = $currentUser->workshops()->pluck('workshops.id');
            $query->whereHas('workshops', fn ($q) => $q->whereIn('workshops.id', $workshopIds));
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"]);
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

        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);

        return UserResource::collection($query->paginate($perPage));
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

        $user->update(['status' => $request->input('status')]);

        return new UserResource($user->load('workshops'));
    }

    public function update(Request $request, User $user): UserResource|JsonResponse
    {
        $request->validate([
            'name'  => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'role'  => ['sometimes', 'required', 'string', 'in:superadmin,admin,user'],
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
            return response()->json(['message' => 'Solo un Super Admin puede cambiar el rol.'], 403);
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

    private function canActOn(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($actor->isAdmin()) {
            $adminWorkshopIds = $actor->workshops()->pluck('workshops.id');
            return $target->workshops()->whereIn('workshops.id', $adminWorkshopIds)->exists();
        }

        return false;
    }
}
