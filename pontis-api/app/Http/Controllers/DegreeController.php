<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserDegree;
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
        $degree = UserDegree::create($data);
        $degree->load('workshop:id,name,number');
        return response()->json($degree, 201);
    }
    public function update(Request $request, UserDegree $degree): JsonResponse {
        $actor = $request->user();
        abort_if($degree->user_id !== $actor->id && !$actor->isSuperAdmin(), 403);
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
        return response()->json($degree->load('workshop:id,name,number'));
    }
    public function destroy(Request $request, UserDegree $degree): JsonResponse {
        abort_if($degree->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $degree->delete();
        return response()->json(null, 204);
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
}
