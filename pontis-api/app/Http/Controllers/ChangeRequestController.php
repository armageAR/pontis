<?php
namespace App\Http\Controllers;
use App\Models\ChangeRequest;
use App\Models\PontisNotification;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChangeRequestController extends Controller {
    public function index(Request $request): JsonResponse {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $query = ChangeRequest::with('user:id,name,last_name,email')->latest();
        } else {
            $adminWorkshopIds = $this->adminWorkshopIds($user);
            $query = $adminWorkshopIds->isEmpty()
                ? ChangeRequest::where('user_id', $user->id)->latest()
                : ChangeRequest::with('user:id,name,last_name,email')
                    ->whereHas('user.workshops', fn ($q) => $q->whereIn('workshops.id', $adminWorkshopIds))
                    ->latest();
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->paginate(20));
    }

    /** IDs of Talleres where the actor has an active admin membership. */
    private function adminWorkshopIds(User $user): Collection {
        return $user->workshops()->wherePivot('role', 'admin')->pluck('workshops.id');
    }

    /**
     * A reviewer may resolve a request when they are Superadmin, or an Admin de
     * Taller of a Taller the request owner actively belongs to.
     */
    private function canReview(ChangeRequest $changeRequest, User $user): bool {
        if ($user->isSuperAdmin()) return true;
        $adminWorkshopIds = $this->adminWorkshopIds($user);
        if ($adminWorkshopIds->isEmpty()) return false;
        return $changeRequest->user->workshops()->whereIn('workshops.id', $adminWorkshopIds)->exists();
    }

    /**
     * Recipients notified when the given owner creates a request: all Superadmins
     * plus Admin de Taller users of the owner's active Talleres, deduplicated and
     * excluding the requesting owner.
     */
    private function reviewerRecipientIds(User $owner): Collection {
        $superadminIds = User::where('role', 'superadmin')->pluck('id');
        $ownerWorkshopIds = $owner->workshops()->pluck('workshops.id');
        $adminIds = $ownerWorkshopIds->isEmpty()
            ? collect()
            : DB::table('user_workshop')
                ->whereIn('workshop_id', $ownerWorkshopIds)
                ->where('role', 'admin')
                ->where('status', 'active')
                ->pluck('user_id');
        return $superadminIds->merge($adminIds)->unique()->reject(fn ($id) => $id === $owner->id)->values();
    }

    public function store(Request $request): JsonResponse {
        $user = $request->user();
        $data = $request->validate([
            'field'     => 'required|string|in:name,last_name,dni,masonic_id',
            'new_value' => 'required|string|max:255',
            'reason'    => 'nullable|string',
        ]);
        $existing = ChangeRequest::where('user_id', $user->id)
            ->where('field', $data['field'])
            ->whereIn('status', ['pending', 'requires_info'])
            ->exists();
        if ($existing) {
            return response()->json(['message' => 'Ya tenés una solicitud pendiente para ese campo.'], 422);
        }
        $data['user_id']       = $user->id;
        $data['current_value'] = $user->{$data['field']};
        $cr = ChangeRequest::create($data);
        AuditLogger::log($request, 'change_request.created', $cr, 'pending', [
            'field' => $cr->field,
            'user_id' => $cr->user_id,
        ]);
        foreach ($this->reviewerRecipientIds($user) as $rid) {
            PontisNotification::create([
                'user_id' => $rid,
                'type'    => 'change_request',
                'title'   => 'Solicitud de cambio sensible',
                'body'    => "{$user->name} solicita cambiar su {$data['field']}.",
                'data'    => ['change_request_id' => $cr->id],
            ]);
        }
        return response()->json($cr, 201);
    }

    public function approve(Request $request, ChangeRequest $changeRequest): JsonResponse {
        abort_unless($this->canReview($changeRequest, $request->user()), 403);
        abort_if(!in_array($changeRequest->status, ['pending', 'requires_info']), 422, 'Esta solicitud ya fue resuelta.');
        $data = $request->validate(['reviewer_notes' => 'nullable|string']);
        $user = $changeRequest->user;
        $user->update([$changeRequest->field => $changeRequest->new_value]);
        $changeRequest->update([
            'status'         => 'approved',
            'reviewer_id'    => $request->user()->id,
            'reviewer_notes' => $data['reviewer_notes'] ?? null,
            'reviewed_at'    => now(),
        ]);
        AuditLogger::log($request, 'change_request.approved', $changeRequest, 'approved', [
            'field' => $changeRequest->field,
            'user_id' => $changeRequest->user_id,
        ]);
        PontisNotification::create([
            'user_id' => $user->id,
            'type'    => 'change_approved',
            'title'   => 'Cambio aprobado',
            'body'    => "Tu solicitud de cambio de {$changeRequest->field} fue aprobada.",
            'data'    => ['change_request_id' => $changeRequest->id],
        ]);
        return response()->json($changeRequest->fresh());
    }

    public function reject(Request $request, ChangeRequest $changeRequest): JsonResponse {
        abort_unless($this->canReview($changeRequest, $request->user()), 403);
        abort_if(!in_array($changeRequest->status, ['pending', 'requires_info']), 422, 'Esta solicitud ya fue resuelta.');
        $data = $request->validate(['reviewer_notes' => 'nullable|string']);
        $changeRequest->update([
            'status'         => 'rejected',
            'reviewer_id'    => $request->user()->id,
            'reviewer_notes' => $data['reviewer_notes'] ?? null,
            'reviewed_at'    => now(),
        ]);
        AuditLogger::log($request, 'change_request.rejected', $changeRequest, 'rejected', [
            'field' => $changeRequest->field,
            'user_id' => $changeRequest->user_id,
        ]);
        PontisNotification::create([
            'user_id' => $changeRequest->user_id,
            'type'    => 'change_rejected',
            'title'   => 'Cambio rechazado',
            'body'    => "Tu solicitud de cambio de {$changeRequest->field} fue rechazada.",
            'data'    => ['change_request_id' => $changeRequest->id],
        ]);
        return response()->json($changeRequest->fresh());
    }

    public function requireInfo(Request $request, ChangeRequest $changeRequest): JsonResponse {
        abort_unless($this->canReview($changeRequest, $request->user()), 403);
        abort_if(!in_array($changeRequest->status, ['pending', 'requires_info']), 422, 'Esta solicitud ya fue resuelta.');
        $data = $request->validate(['reviewer_notes' => 'required|string']);
        $changeRequest->update([
            'status'         => 'requires_info',
            'reviewer_id'    => $request->user()->id,
            'reviewer_notes' => $data['reviewer_notes'],
            'reviewed_at'    => now(),
        ]);
        AuditLogger::log($request, 'change_request.requires_info', $changeRequest, 'requires_info', [
            'field' => $changeRequest->field,
            'user_id' => $changeRequest->user_id,
        ]);
        PontisNotification::create([
            'user_id' => $changeRequest->user_id,
            'type'    => 'change_requires_info',
            'title'   => 'Se requiere información adicional',
            'body'    => "Tu solicitud de cambio de {$changeRequest->field} requiere información adicional: {$data['reviewer_notes']}",
            'data'    => ['change_request_id' => $changeRequest->id],
        ]);
        return response()->json($changeRequest->fresh());
    }

    public function cancel(Request $request, ChangeRequest $changeRequest): JsonResponse {
        abort_if($changeRequest->user_id !== $request->user()->id, 403);
        abort_if(!in_array($changeRequest->status, ['pending', 'requires_info']), 422, 'Esta solicitud ya fue resuelta.');
        $changeRequest->update(['status' => 'cancelled_by_user', 'reviewed_at' => now()]);
        AuditLogger::log($request, 'change_request.cancelled', $changeRequest, 'cancelled_by_user', [
            'field' => $changeRequest->field,
        ]);
        return response()->json($changeRequest->fresh());
    }
}
