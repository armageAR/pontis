<?php
namespace App\Http\Controllers;
use App\Models\PontisNotification;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller {
    private function userIsMaestro(User $user): bool {
        return $user->userDegrees()
            ->where('degree', 'maestro')
            ->where(function ($q) { $q->whereNull('end_date')->orWhere('end_date', '>=', now()); })
            ->exists();
    }

    public function index(Request $request): JsonResponse {
        $user = $request->user();
        $query = $user->isSuperAdmin()
            ? Service::with(['user:id,name,last_name', 'category:id,name'])
            : Service::where('user_id', $user->id)->with(['category:id,name']);
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'service_category_id' => 'nullable|integer|exists:service_categories,id',
            'modality'            => 'nullable|string|in:presencial,remoto,both',
            'location'            => 'nullable|string|max:255',
            'availability'        => 'nullable|string',
            'conditions'          => 'nullable|string',
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,talleres_seleccionados,registered,anonymous',
            'status'              => 'nullable|string|in:draft,active,paused,hidden,disabled,pending_authorization,requires_correction,rejected,closed,cancelled',
        ]);
        $user = $request->user();
        $data['user_id'] = $user->id;
        // Apply authorization flow: non-Maestros require authorization for active/public publications
        $requestedStatus = $data['status'] ?? 'draft';
        if (in_array($requestedStatus, ['active']) && !$this->userIsMaestro($user)) {
            $data['status'] = 'pending_authorization';
            $this->notifyWorkshopAdmins($user, 'service', $data['title']);
        } else {
            $data['status'] = $requestedStatus;
        }
        $service = Service::create($data);
        $service->load('category:id,name');
        return response()->json($service, 201);
    }

    public function show(Request $request, Service $service): JsonResponse {
        abort_if($service->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        return response()->json($service->load(['user:id,name,last_name', 'category:id,name']));
    }

    public function update(Request $request, Service $service): JsonResponse {
        abort_if($service->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'title'               => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'service_category_id' => 'nullable|integer|exists:service_categories,id',
            'modality'            => 'nullable|string|in:presencial,remoto,both',
            'location'            => 'nullable|string|max:255',
            'availability'        => 'nullable|string',
            'conditions'          => 'nullable|string',
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,talleres_seleccionados,registered,anonymous',
            'status'              => 'nullable|string|in:draft,active,paused,hidden,disabled,pending_authorization,requires_correction,rejected,closed,cancelled',
        ]);
        // If owner (not superadmin) changes requires_correction → active, apply approval logic
        if (!$request->user()->isSuperAdmin() && isset($data['status']) && $data['status'] === 'active'
            && $service->status === 'requires_correction' && !$this->userIsMaestro($request->user())) {
            $data['status'] = 'pending_authorization';
            $this->notifyWorkshopAdmins($request->user(), 'service', $service->title);
        }
        $service->update($data);
        return response()->json($service->load('category:id,name'));
    }

    public function destroy(Request $request, Service $service): JsonResponse {
        abort_if($service->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $service->delete();
        return response()->json(null, 204);
    }

    public function authorize(Request $request, Service $service): JsonResponse {
        abort_unless($request->user()->isSuperAdmin() || $this->isWorkshopAdmin($request->user(), $service->user), 403);
        $data = $request->validate(['notes' => 'nullable|string']);
        $service->update([
            'status'            => 'active',
            'authorized_by'     => $request->user()->id,
            'authorized_at'     => now(),
            'authorization_notes' => $data['notes'] ?? null,
        ]);
        PontisNotification::create([
            'user_id' => $service->user_id,
            'type'    => 'publication_authorized',
            'title'   => 'Publicación aprobada',
            'body'    => "Tu servicio \"{$service->title}\" fue aprobado y ya está activo.",
            'data'    => ['service_id' => $service->id],
        ]);
        return response()->json($service->fresh()->load('category:id,name'));
    }

    public function rejectPublication(Request $request, Service $service): JsonResponse {
        abort_unless($request->user()->isSuperAdmin() || $this->isWorkshopAdmin($request->user(), $service->user), 403);
        $data = $request->validate(['notes' => 'nullable|string']);
        $service->update([
            'status'            => 'rejected',
            'authorized_by'     => $request->user()->id,
            'authorized_at'     => now(),
            'authorization_notes' => $data['notes'] ?? null,
        ]);
        PontisNotification::create([
            'user_id' => $service->user_id,
            'type'    => 'publication_rejected',
            'title'   => 'Publicación rechazada',
            'body'    => "Tu servicio \"{$service->title}\" fue rechazado." . ($data['notes'] ? " Motivo: {$data['notes']}" : ''),
            'data'    => ['service_id' => $service->id],
        ]);
        return response()->json($service->fresh()->load('category:id,name'));
    }

    public function requestCorrection(Request $request, Service $service): JsonResponse {
        abort_unless($request->user()->isSuperAdmin() || $this->isWorkshopAdmin($request->user(), $service->user), 403);
        $data = $request->validate(['notes' => 'required|string']);
        $service->update(['status' => 'requires_correction', 'authorization_notes' => $data['notes']]);
        PontisNotification::create([
            'user_id' => $service->user_id,
            'type'    => 'publication_needs_correction',
            'title'   => 'Publicación requiere corrección',
            'body'    => "Tu servicio \"{$service->title}\" necesita correcciones: {$data['notes']}",
            'data'    => ['service_id' => $service->id],
        ]);
        return response()->json($service->fresh()->load('category:id,name'));
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
                'body'    => "{$user->name} creó un {$type} que requiere tu aprobación: \"{$title}\".",
                'data'    => ['user_id' => $user->id],
            ]);
        }
    }

    private function isWorkshopAdmin(User $admin, User $owner): bool {
        $ownerWorkshopIds = $owner->workshops()->pluck('workshops.id');
        return $admin->workshops()->whereIn('workshops.id', $ownerWorkshopIds)->wherePivot('role', 'admin')->exists();
    }
}
