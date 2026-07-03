<?php
namespace App\Http\Controllers;
use App\Models\ChangeRequest;
use App\Models\PontisNotification;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangeRequestController extends Controller {
    public function index(Request $request): JsonResponse {
        $user = $request->user();
        $query = $user->isSuperAdmin()
            ? ChangeRequest::with('user:id,name,last_name,email')->latest()
            : ChangeRequest::where('user_id', $user->id)->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->paginate(20));
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
        $superadmins = \App\Models\User::where('role', 'superadmin')->pluck('id');
        foreach ($superadmins as $sid) {
            PontisNotification::create([
                'user_id' => $sid,
                'type'    => 'change_request',
                'title'   => 'Solicitud de cambio sensible',
                'body'    => "{$user->name} solicita cambiar su {$data['field']}.",
                'data'    => ['change_request_id' => $cr->id],
            ]);
        }
        return response()->json($cr, 201);
    }

    public function approve(Request $request, ChangeRequest $changeRequest): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
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
        abort_unless($request->user()->isSuperAdmin(), 403);
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
        abort_unless($request->user()->isSuperAdmin(), 403);
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
