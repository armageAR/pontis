<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\PontisNotification;
use App\Support\AuditLogger;
use App\Support\DegreeProgression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
class DegreeController extends Controller {
    public function index(Request $request): JsonResponse {
        $degrees = $request->user()->userDegrees()->with('workshop:id,name,number')->orderByDesc('start_date')->get();

        // Fin de período derivado: cada grado termina cuando inicia el siguiente
        // (por fecha de inicio). El grado más reciente queda vigente (end_date null).
        $asc = $degrees->sortBy('start_date')->values();
        for ($i = 0; $i < $asc->count(); $i++) {
            $asc[$i]->end_date = $asc[$i + 1]->start_date ?? null;
        }

        return response()->json($degrees);
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'degree'      => 'required|string|in:aprendiz,companero,maestro',
            'workshop_id' => 'nullable|integer|exists:workshops,id',
            'start_date'  => 'required|date',
            'notes'       => 'nullable|string',
        ]);
        $user = $request->user();

        if ($error = $this->validateWorkshopAndSequence($user, $data['workshop_id'] ?? null, $data['degree'], $data['start_date'])) {
            return $error;
        }

        $data['user_id'] = $user->id;
        // Si el actor ya está autorizado a validar para el Taller declarado
        // (Superadmin, o Admin del Taller indicado), el registro autopropio se
        // guarda validado y no genera pendientes ni notificaciones de revisión.
        $autoValidated = $this->canValidateForWorkshop($user, $data['workshop_id'] ?? null);
        $data['validation_status'] = $autoValidated ? 'validated' : 'declared';
        if ($autoValidated) {
            $data['validator_id'] = $user->id;
            $data['validated_at'] = now();
        }
        $degree = UserDegree::create($data);
        AuditLogger::log($request, $autoValidated ? 'degree.self_validated' : 'degree.created', $degree, $autoValidated ? 'validated' : 'created', ['degree' => $degree->degree, 'workshop_id' => $degree->workshop_id]);
        if (! $autoValidated) {
            $this->notifyValidators($degree, 'degree_validation_pending', 'Grado pendiente de validación');
        }
        $degree->load('workshop:id,name,number');
        return response()->json($degree, 201);
    }
    public function storeForUser(Request $request, User $user): JsonResponse {
        $actor = $request->user();
        $data = $request->validate([
            'degree'      => 'required|string|in:aprendiz,companero,maestro',
            'workshop_id' => 'nullable|integer|exists:workshops,id',
            'start_date'  => 'required|date',
            'notes'       => 'nullable|string',
        ]);
        abort_unless($this->canValidateForWorkshop($actor, $data['workshop_id'] ?? null), 403);
        if ($error = $this->validateWorkshopAndSequence($user, $data['workshop_id'] ?? null, $data['degree'], $data['start_date'])) return $error;
        $data['user_id'] = $user->id;
        $data['validation_status'] = 'validated';
        $data['validator_id'] = $actor->id;
        $data['validated_at'] = now();
        $degree = UserDegree::create($data);
        AuditLogger::log($request, 'degree.admin_created', $degree, 'validated', ['degree' => $degree->degree, 'workshop_id' => $degree->workshop_id]);
        return response()->json($degree->load('workshop:id,name,number'), 201);
    }
    public function update(Request $request, UserDegree $degree): JsonResponse {
        $actor = $request->user();
        abort_if($degree->user_id !== $actor->id && !$actor->isSuperAdmin(), 403);
        abort_if($degree->validation_status === 'validated' && $degree->user_id === $actor->id && !$actor->isSuperAdmin(), 422, 'Los registros validados no pueden editarse directamente.');
        $data = $request->validate([
            'degree'      => 'sometimes|string|in:aprendiz,companero,maestro',
            'workshop_id' => 'nullable|integer|exists:workshops,id',
            'start_date'  => 'sometimes|date',
            'notes'       => 'nullable|string',
        ]);

        // La validación se hace contra el Hermano dueño del historial, no el actor.
        $owner = $degree->user;
        $newWorkshopId = array_key_exists('workshop_id', $data) ? $data['workshop_id'] : $degree->workshop_id;
        $newDegree = $data['degree'] ?? $degree->degree;
        $newStart = isset($data['start_date']) ? $data['start_date'] : $degree->start_date->format('Y-m-d');

        if ($error = $this->validateWorkshopAndSequence($owner, $newWorkshopId, $newDegree, $newStart, $degree->id)) {
            return $error;
        }

        $degree->update($data);
        AuditLogger::log($request, 'degree.updated', $degree, 'updated', ['fields' => array_keys($data), 'degree' => $degree->degree, 'workshop_id' => $degree->workshop_id]);
        return response()->json($degree->load('workshop:id,name,number'));
    }
    public function destroy(Request $request, UserDegree $degree): JsonResponse {
        abort_if($degree->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        abort_if($degree->validation_status === 'validated' && $degree->user_id === $request->user()->id && !$request->user()->isSuperAdmin(), 422, 'Los registros validados no pueden eliminarse directamente.');
        $degree->delete();
        AuditLogger::log($request, 'degree.deleted', $degree, 'deleted');
        return response()->json(null, 204);
    }

    public function pendingValidations(Request $request): JsonResponse {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin() || $actor->isAdminOfAnyWorkshop(), 403);
        $query = UserDegree::with(['user:id,name,last_name,email', 'workshop:id,name,number'])
            ->where('validation_status', 'declared');
        if (! $actor->isSuperAdmin()) {
            $adminWorkshopIds = $actor->workshops()->wherePivot('role', 'admin')->pluck('workshops.id');
            $query->whereIn('workshop_id', $adminWorkshopIds);
        }
        return response()->json($query->latest()->paginate(20));
    }

    public function validateDeclaration(Request $request, UserDegree $degree): JsonResponse {
        abort_unless($this->canValidateForWorkshop($request->user(), $degree->workshop_id), 403);
        abort_if($degree->validation_status !== 'declared', 422, 'El registro no está pendiente de validación.');
        $degree->update(['validation_status' => 'validated', 'validator_id' => $request->user()->id, 'validated_at' => now()]);
        PontisNotification::create(['user_id'=>$degree->user_id,'type'=>'degree_validated','title'=>'Grado validado','body'=>'Tu declaración de grado fue validada.','data'=>['degree_id'=>$degree->id]]);
        AuditLogger::log($request, 'degree.validated', $degree, 'validated', ['degree' => $degree->degree, 'workshop_id' => $degree->workshop_id]);
        return response()->json($degree->fresh()->load('workshop:id,name,number'));
    }

    public function rejectDeclaration(Request $request, UserDegree $degree): JsonResponse {
        abort_unless($this->canValidateForWorkshop($request->user(), $degree->workshop_id), 403);
        abort_if($degree->validation_status !== 'declared', 422, 'El registro no está pendiente de validación.');
        $data = $request->validate(['validation_notes' => 'nullable|string|max:1000']);
        $degree->update(['validation_status' => 'rejected', 'validator_id' => $request->user()->id, 'validated_at' => now(), 'validation_notes' => $data['validation_notes'] ?? null]);
        PontisNotification::create(['user_id'=>$degree->user_id,'type'=>'degree_rejected','title'=>'Grado rechazado','body'=>'Tu declaración de grado fue rechazada.','data'=>['degree_id'=>$degree->id]]);
        AuditLogger::log($request, 'degree.rejected', $degree, 'rejected', ['degree' => $degree->degree, 'workshop_id' => $degree->workshop_id]);
        return response()->json($degree->fresh()->load('workshop:id,name,number'));
    }

    /**
     * Valida que el Taller (si se indica) pertenezca al Hermano y que el grado
     * resultante respete la progresión estricta. Devuelve una JsonResponse 422
     * si algo falla, o null si es válido.
     */
    private function validateWorkshopAndSequence(User $owner, ?int $workshopId, string $degree, string $startDate, ?int $excludeId = null): ?JsonResponse {
        if (! empty($workshopId) && ! $owner->workshops()->where('workshops.id', $workshopId)->exists()) {
            return response()->json(['message' => DegreeProgression::WORKSHOP_ERROR], 422);
        }

        $others = $owner->userDegrees()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get(['degree', 'start_date'])
            ->map(fn ($d) => ['degree' => $d->degree, 'start' => $d->start_date->format('Y-m-d')])
            ->all();
        $candidate = [...$others, ['degree' => $degree, 'start' => Carbon::parse($startDate)->format('Y-m-d')]];

        if ($message = DegreeProgression::validate($candidate)) {
            return response()->json(['message' => $message], 422);
        }

        return null;
    }

    private function canValidateForWorkshop(User $actor, ?int $workshopId): bool {
        if ($actor->isSuperAdmin()) return true;
        return ! empty($workshopId) && $actor->workshops()
            ->where('workshops.id', $workshopId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    private function notifyValidators(UserDegree $degree, string $type, string $title): void {
        $admins = User::query()
            ->when($degree->workshop_id, fn($q) => $q->whereHas('workshops', fn($w) => $w->where('workshops.id', $degree->workshop_id)->where('user_workshop.role', 'admin')))
            ->orWhere('role', 'superadmin')
            ->get();
        foreach ($admins as $admin) {
            PontisNotification::create(['user_id'=>$admin->id,'type'=>$type,'title'=>$title,'body'=>'Hay una declaración masónica pendiente de revisión.','data'=>['degree_id'=>$degree->id]]);
        }
    }
}
