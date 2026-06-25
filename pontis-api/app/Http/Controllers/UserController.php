<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
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

        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdmin()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'No podés cambiar tu propio estado.'], 403);
        }

        if ($currentUser->isAdmin()) {
            $adminWorkshopIds = $currentUser->workshops()->pluck('workshops.id');
            $userInAdminWorkshops = $user->workshops()->whereIn('workshops.id', $adminWorkshopIds)->exists();

            if (! $userInAdminWorkshops) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        }

        $user->update(['status' => $request->input('status')]);

        return new UserResource($user->load('workshops'));
    }
}
