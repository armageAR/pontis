<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\PontisNotification;
use App\Models\UserPosition;
use App\Support\AuditLogger;
use App\Support\DegreeProgression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class UserPositionController extends Controller {
    public function index(Request $request): JsonResponse {
        $positions = $request->user()->userPositions()->with(['position:id,name','workshop:id,name,number'])->orderByDesc('start_date')->get();
        return response()->json($positions);
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'position_id' => 'required|integer|exists:positions,id',
            'workshop_id' => 'required|integer|exists:workshops,id',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after:start_date',
            'notes'       => 'nullable|string',
        ]);
        $user = $request->user();

        if (! $this->workshopBelongsTo($user, $data['workshop_id'])) {
            return response()->json(['message' => DegreeProgression::WORKSHOP_ERROR], 422);
        }

        $data['user_id'] = $user->id;
        $data['validation_status'] = 'declared';
        $up = UserPosition::create($data);
        AuditLogger::log($request, 'position.created', $up, 'created', ['position_id' => $up->position_id, 'workshop_id' => $up->workshop_id]);
        $this->notifyValidators($up, 'position_validation_pending', 'Cargo pendiente de validación');
        $up->load(['position:id,name','workshop:id,name,number']);
        return response()->json($up, 201);
    }
    public function storeForUser(Request $request, User $user): JsonResponse {
        $actor = $request->user();
        $data = $request->validate([
            'position_id' => 'required|integer|exists:positions,id',
            'workshop_id' => 'required|integer|exists:workshops,id',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after:start_date',
            'notes'       => 'nullable|string',
        ]);
        abort_unless($this->canValidateForWorkshop($actor, $data['workshop_id']), 403);
        if (! $this->workshopBelongsTo($user, $data['workshop_id'])) return response()->json(['message' => DegreeProgression::WORKSHOP_ERROR], 422);
        $data['user_id'] = $user->id;
        $data['validation_status'] = 'validated';
        $data['validator_id'] = $actor->id;
        $data['validated_at'] = now();
        $up = UserPosition::create($data);
        AuditLogger::log($request, 'position.admin_created', $up, 'validated', ['position_id' => $up->position_id, 'workshop_id' => $up->workshop_id]);
        return response()->json($up->load(['position:id,name','workshop:id,name,number']), 201);
    }
    public function update(Request $request, UserPosition $userPosition): JsonResponse {
        $actor = $request->user();
        abort_if($userPosition->user_id !== $actor->id && !$actor->isSuperAdmin(), 403);
        abort_if($userPosition->validation_status === 'validated' && $userPosition->user_id === $actor->id && !$actor->isSuperAdmin(), 422, 'Los registros validados no pueden editarse directamente.');
        $data = $request->validate([
            'position_id' => 'sometimes|integer|exists:positions,id',
            'workshop_id' => 'sometimes|integer|exists:workshops,id',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        // La pertenencia se valida contra el Hermano dueño del cargo, no el actor.
        $owner = $userPosition->user;
        $newWorkshopId = array_key_exists('workshop_id', $data) ? $data['workshop_id'] : $userPosition->workshop_id;
        if (! $this->workshopBelongsTo($owner, $newWorkshopId)) {
            return response()->json(['message' => DegreeProgression::WORKSHOP_ERROR], 422);
        }

        $userPosition->update($data);
        AuditLogger::log($request, 'position.updated', $userPosition, 'updated', ['fields' => array_keys($data), 'position_id' => $userPosition->position_id, 'workshop_id' => $userPosition->workshop_id]);
        return response()->json($userPosition->load(['position:id,name','workshop:id,name,number']));
    }
    public function destroy(Request $request, UserPosition $userPosition): JsonResponse {
        abort_if($userPosition->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        abort_if($userPosition->validation_status === 'validated' && $userPosition->user_id === $request->user()->id && !$request->user()->isSuperAdmin(), 422, 'Los registros validados no pueden eliminarse directamente.');
        $userPosition->delete();
        AuditLogger::log($request, 'position.deleted', $userPosition, 'deleted');
        return response()->json(null, 204);
    }

    public function pendingValidations(Request $request): JsonResponse {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin() || $actor->isAdminOfAnyWorkshop(), 403);
        $query = UserPosition::with(['user:id,name,last_name,email', 'position:id,name', 'workshop:id,name,number'])
            ->where('validation_status', 'declared');
        if (! $actor->isSuperAdmin()) {
            $adminWorkshopIds = $actor->workshops()->wherePivot('role', 'admin')->pluck('workshops.id');
            $query->whereIn('workshop_id', $adminWorkshopIds);
        }
        return response()->json($query->latest()->paginate(20));
    }

    public function validateDeclaration(Request $request, UserPosition $userPosition): JsonResponse {
        abort_unless($this->canValidateForWorkshop($request->user(), $userPosition->workshop_id), 403);
        abort_if($userPosition->validation_status !== 'declared', 422, 'El registro no está pendiente de validación.');
        $userPosition->update(['validation_status' => 'validated', 'validator_id' => $request->user()->id, 'validated_at' => now()]);
        PontisNotification::create(['user_id'=>$userPosition->user_id,'type'=>'position_validated','title'=>'Cargo validado','body'=>'Tu declaración de cargo fue validada.','data'=>['position_id'=>$userPosition->id]]);
        AuditLogger::log($request, 'position.validated', $userPosition, 'validated', ['position_id' => $userPosition->position_id, 'workshop_id' => $userPosition->workshop_id]);
        return response()->json($userPosition->fresh()->load(['position:id,name','workshop:id,name,number']));
    }

    public function rejectDeclaration(Request $request, UserPosition $userPosition): JsonResponse {
        abort_unless($this->canValidateForWorkshop($request->user(), $userPosition->workshop_id), 403);
        abort_if($userPosition->validation_status !== 'declared', 422, 'El registro no está pendiente de validación.');
        $data = $request->validate(['validation_notes' => 'nullable|string|max:1000']);
        $userPosition->update(['validation_status' => 'rejected', 'validator_id' => $request->user()->id, 'validated_at' => now(), 'validation_notes' => $data['validation_notes'] ?? null]);
        PontisNotification::create(['user_id'=>$userPosition->user_id,'type'=>'position_rejected','title'=>'Cargo rechazado','body'=>'Tu declaración de cargo fue rechazada.','data'=>['position_id'=>$userPosition->id]]);
        AuditLogger::log($request, 'position.rejected', $userPosition, 'rejected', ['position_id' => $userPosition->position_id, 'workshop_id' => $userPosition->workshop_id]);
        return response()->json($userPosition->fresh()->load(['position:id,name','workshop:id,name,number']));
    }

    private function workshopBelongsTo(User $owner, int $workshopId): bool {
        return $owner->workshops()->where('workshops.id', $workshopId)->exists();
    }

    private function canValidateForWorkshop(User $actor, int $workshopId): bool {
        if ($actor->isSuperAdmin()) return true;
        return $actor->workshops()
            ->where('workshops.id', $workshopId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    private function notifyValidators(UserPosition $position, string $type, string $title): void {
        $admins = User::query()
            ->whereHas('workshops', fn($w) => $w->where('workshops.id', $position->workshop_id)->where('user_workshop.role', 'admin'))
            ->orWhere('role', 'superadmin')
            ->get();
        foreach ($admins as $admin) {
            PontisNotification::create(['user_id'=>$admin->id,'type'=>$type,'title'=>$title,'body'=>'Hay una declaración masónica pendiente de revisión.','data'=>['position_id'=>$position->id]]);
        }
    }
}
