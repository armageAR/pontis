<?php
namespace App\Http\Controllers;
use App\Models\Need;
use App\Models\PontisNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NeedController extends Controller {
    private function userIsMaestro(User $user): bool {
        return $user->userDegrees()
            ->where('degree', 'maestro')
            ->where(function ($q) { $q->whereNull('end_date')->orWhere('end_date', '>=', now()); })
            ->exists();
    }

    public function index(Request $request): JsonResponse {
        $user = $request->user();
        $query = $user->isSuperAdmin()
            ? Need::with(['user:id,name,last_name', 'category:id,name'])
            : Need::where('user_id', $user->id)->with(['category:id,name']);
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'service_category_id' => 'nullable|integer|exists:service_categories,id',
            'location'            => 'nullable|string|max:255',
            'urgency'             => 'nullable|string|in:low,medium,high',
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,talleres_seleccionados,registered,anonymous',
            'status'              => 'nullable|string|in:draft,open,searching,with_matches,contact_requested,linked,closed,cancelled,pending_authorization,requires_correction,rejected',
        ]);
        $user = $request->user();
        $data['user_id'] = $user->id;
        $requestedStatus = $data['status'] ?? 'draft';
        if (in_array($requestedStatus, ['open', 'searching']) && !$this->userIsMaestro($user)) {
            $data['status'] = 'pending_authorization';
            $this->notifyWorkshopAdmins($user, 'need', $data['title']);
        } else {
            $data['status'] = $requestedStatus;
        }
        $need = Need::create($data);
        $need->load('category:id,name');
        return response()->json($need, 201);
    }

    public function show(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        return response()->json($need->load(['user:id,name,last_name', 'category:id,name']));
    }

    public function update(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'title'               => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'service_category_id' => 'nullable|integer|exists:service_categories,id',
            'location'            => 'nullable|string|max:255',
            'urgency'             => 'nullable|string|in:low,medium,high',
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,talleres_seleccionados,registered,anonymous',
            'status'              => 'nullable|string|in:draft,open,searching,with_matches,contact_requested,linked,closed,cancelled,pending_authorization,requires_correction,rejected',
        ]);
        if (!$request->user()->isSuperAdmin() && isset($data['status']) && in_array($data['status'], ['open', 'searching'])
            && $need->status === 'requires_correction' && !$this->userIsMaestro($request->user())) {
            $data['status'] = 'pending_authorization';
            $this->notifyWorkshopAdmins($request->user(), 'need', $need->title);
        }
        $need->update($data);
        return response()->json($need->load('category:id,name'));
    }

    public function destroy(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $need->delete();
        return response()->json(null, 204);
    }

    public function authorize(Request $request, Need $need): JsonResponse {
        abort_unless($request->user()->isSuperAdmin() || $this->isWorkshopAdmin($request->user(), $need->user), 403);
        $data = $request->validate(['notes' => 'nullable|string']);
        $need->update([
            'status'              => 'open',
            'authorized_by'       => $request->user()->id,
            'authorized_at'       => now(),
            'authorization_notes' => $data['notes'] ?? null,
        ]);
        PontisNotification::create([
            'user_id' => $need->user_id,
            'type'    => 'publication_authorized',
            'title'   => 'Necesidad aprobada',
            'body'    => "Tu necesidad \"{$need->title}\" fue aprobada y ya está activa.",
            'data'    => ['need_id' => $need->id],
        ]);
        return response()->json($need->fresh()->load('category:id,name'));
    }

    public function rejectPublication(Request $request, Need $need): JsonResponse {
        abort_unless($request->user()->isSuperAdmin() || $this->isWorkshopAdmin($request->user(), $need->user), 403);
        $data = $request->validate(['notes' => 'nullable|string']);
        $need->update([
            'status'              => 'rejected',
            'authorized_by'       => $request->user()->id,
            'authorized_at'       => now(),
            'authorization_notes' => $data['notes'] ?? null,
        ]);
        PontisNotification::create([
            'user_id' => $need->user_id,
            'type'    => 'publication_rejected',
            'title'   => 'Necesidad rechazada',
            'body'    => "Tu necesidad \"{$need->title}\" fue rechazada." . ($data['notes'] ? " Motivo: {$data['notes']}" : ''),
            'data'    => ['need_id' => $need->id],
        ]);
        return response()->json($need->fresh()->load('category:id,name'));
    }

    public function requestCorrection(Request $request, Need $need): JsonResponse {
        abort_unless($request->user()->isSuperAdmin() || $this->isWorkshopAdmin($request->user(), $need->user), 403);
        $data = $request->validate(['notes' => 'required|string']);
        $need->update(['status' => 'requires_correction', 'authorization_notes' => $data['notes']]);
        PontisNotification::create([
            'user_id' => $need->user_id,
            'type'    => 'publication_needs_correction',
            'title'   => 'Necesidad requiere corrección',
            'body'    => "Tu necesidad \"{$need->title}\" necesita correcciones: {$data['notes']}",
            'data'    => ['need_id' => $need->id],
        ]);
        return response()->json($need->fresh()->load('category:id,name'));
    }

    private function notifyWorkshopAdmins(User $user, string $type, string $title): void {
        $workshopId = $user->workshops()->wherePivot('role', 'admin')->value('workshops.id');
        if (!$workshopId) {
            $workshopId = $user->workshops()->first()?->id;
        }
        $admins = User::whereHas('workshops', fn($q) => $q->where('workshops.id', $workshopId)->where('user_workshop.role', 'admin'))->pluck('id');
        $superadmins = User::where('role', 'superadmin')->pluck('id');
        foreach ($admins->merge($superadmins)->unique() as $adminId) {
            PontisNotification::create([
                'user_id' => $adminId,
                'type'    => "pending_{$type}_authorization",
                'title'   => 'Publicación pendiente de autorización',
                'body'    => "{$user->name} creó una necesidad que requiere tu aprobación: \"{$title}\".",
                'data'    => ['user_id' => $user->id],
            ]);
        }
    }

    private function isWorkshopAdmin(User $admin, ?User $owner): bool {
        if (!$owner) return false;
        $ownerWorkshopIds = $owner->workshops()->pluck('workshops.id');
        return $admin->workshops()->whereIn('workshops.id', $ownerWorkshopIds)->wherePivot('role', 'admin')->exists();
    }
}
