<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminUserResource;
use App\Models\ContactRequest;
use App\Models\Need;
use App\Models\Service;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Support\AuditLogger;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search'         => ['nullable', 'string', 'max:255'],
            'role'           => ['nullable', 'string', 'in:superadmin,user'],
            'status'         => ['nullable', 'string', 'in:pending,active,rejected,suspended,inactive,o_eterno'],
            'workshop_id'    => ['nullable', 'integer', 'exists:workshops,id'],
            'workshop_role'  => ['nullable', 'string', 'in:admin,member'],
            'province'       => ['nullable', 'string', 'max:100'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'        => ['nullable', 'string', 'in:last_name,name,email,workshops,status,actions,created_at,role'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $currentUser = $request->user();

        // Pantalla de Hermanos: payload administrativo sensible (email, estado,
        // membresías). Exclusiva del Superadmin. Las tareas del Admin de Taller
        // viven en Administración → Validaciones; la comunidad usa /people.
        abort_unless($currentUser->isSuperAdmin(), 403);

        $query = User::with('workshops');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('unaccent(LOWER(name)) like unaccent(?)', ["%{$search}%"])
                  ->orWhereRaw('unaccent(LOWER(last_name)) like unaccent(?)', ["%{$search}%"])
                  ->orWhereRaw('unaccent(LOWER(email)) like unaccent(?)', ["%{$search}%"])
                  ->orWhereRaw("CAST(masonic_id AS TEXT) like ?", ["%{$search}%"]);
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

        if ($request->filled('province')) {
            $query->where('province', $request->input('province'));
        }

        $this->applySort($query, $request->input('sort_by', 'last_name'), $request->input('sort_direction', 'asc'));

        $perPage = $request->input('per_page', 15);

        return AdminUserResource::collection($query->paginate($perPage));
    }

    /**
     * Ordena el listado por una columna visible de la tabla de Hermanos. Para
     * las columnas que no son un campo directo del usuario se aplica un orden
     * determinístico y siempre se desempata por id para paginación estable.
     */
    private function applySort($query, string $sortBy, string $sortDir): void
    {
        $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        switch ($sortBy) {
            case 'workshops':
                // Orden por el número de Taller más bajo del Hermano.
                $query->orderByRaw(
                    '(select min(w.number) from user_workshop uw'
                    . ' inner join workshops w on w.id = uw.workshop_id'
                    . " where uw.user_id = users.id) {$dir}"
                );
                break;

            case 'actions':
                // La columna Acciones no es un dato del usuario: se ordena por
                // disponibilidad de acciones administrativas (los superadmin
                // exponen más acciones) y se documenta como determinístico.
                $query->orderByRaw("(case when role = 'superadmin' then 1 else 0 end) {$dir}");
                break;

            default:
                // last_name, name, email, status, created_at, role.
                $query->orderBy($sortBy, $dir);
                break;
        }

        $query->orderBy('users.id', 'asc');
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

    public function updateStatus(Request $request, User $user): AdminUserResource|JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,active,rejected,suspended,inactive'],
        ]);

        $currentUser = $request->user();

        if (! $this->canActOn($currentUser, $user)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $newStatus = $request->input('status');
        $oldStatus = $user->status?->value ?? (string) $user->status;
        $user->status = $newStatus;

        // Al activar manualmente un usuario que aún no verificó su email,
        // se da por validado y se sella la fecha de verificación con hoy.
        // Se asigna directamente porque email_verified_at no es mass-assignable.
        if ($newStatus === 'active' && is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        $user->save();
        AuditLogger::log($request, 'user.status_changed', $user, $newStatus, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return new AdminUserResource($user->load('workshops'));
    }

    public function markOEterno(Request $request, User $user): AdminUserResource|JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_if($request->user()->id === $user->id, 422, 'No podés marcarte como O Eterno.');

        $oldStatus = $user->status?->value ?? (string) $user->status;
        $user->update(['status' => 'o_eterno', 'masonic_status' => 'deceased']);
        $user->tokens()->delete();

        ContactRequest::where(function ($q) use ($user) {
            $q->where('requester_id', $user->id)->orWhere('requestee_id', $user->id);
        })->whereIn('status', ['pending', 'info_requested'])->update(['status' => 'closed']);

        Service::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'suspended']);
        Need::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'suspended']);

        AuditLogger::log($request, 'user.marked_o_eterno', $user, 'o_eterno', ['old_status' => $oldStatus]);

        return new AdminUserResource($user->fresh()->load('workshops'));
    }

    public function revertOEterno(Request $request, User $user): AdminUserResource|JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_unless(($user->status?->value ?? (string) $user->status) === 'o_eterno', 422, 'El usuario no está marcado como O Eterno.');

        $user->update(['status' => 'inactive', 'masonic_status' => 'inactive']);
        AuditLogger::log($request, 'user.reverted_o_eterno', $user, 'inactive');

        return new AdminUserResource($user->fresh()->load('workshops'));
    }

    public function update(Request $request, User $user): AdminUserResource|JsonResponse
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

        $changes = $request->only(['name', 'email', 'role']);
        $user->update($changes);
        AuditLogger::log($request, 'user.updated', $user, 'updated', [
            'fields' => array_keys($changes),
        ]);

        return new AdminUserResource($user->load('workshops'));
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
        AuditLogger::log($request, 'user.password_changed', $user, 'updated');

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    public function addWorkshop(Request $request, User $user, Workshop $workshop): AdminUserResource|JsonResponse
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
        AuditLogger::log($request, 'membership.added', $user, 'added', ['workshop_id' => $workshop->id]);

        return new AdminUserResource($user->load('workshops'));
    }

    public function updateWorkshopRole(Request $request, User $user, Workshop $workshop): AdminUserResource|JsonResponse
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
        AuditLogger::log($request, 'membership.updated', $user, 'updated', ['workshop_id' => $workshop->id, 'fields' => array_keys($pivot)]);

        return new AdminUserResource($user->load('workshops'));
    }

    public function removeWorkshop(Request $request, User $user, Workshop $workshop): AdminUserResource|JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'No podés modificarte a vos mismo.'], 403);
        }

        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdminOfWorkshop($workshop)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $user->workshops()->detach($workshop->id);
        AuditLogger::log($request, 'membership.removed', $user, 'removed', ['workshop_id' => $workshop->id]);

        return new AdminUserResource($user->load('workshops'));
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
