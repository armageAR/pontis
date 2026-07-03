<?php
namespace App\Http\Controllers;
use App\Models\Need;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NeedController extends Controller {
    /** Días de validez permitidos para una publicación (máximo 90). */
    public const ALLOWED_VALIDITY_DAYS = [10, 30, 60, 90];

    public function index(Request $request): JsonResponse {
        $user = $request->user();
        $query = $user->isSuperAdmin()
            ? Need::with(['user:id,name,last_name', 'category:id,name'])
            : Need::where('user_id', $user->id)->with(['category:id,name']);
        $this->applyStatusFilter($query, $request->input('status'));
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) $perPage = $query->count() ?: 1;
        return response()->json($query->orderByDesc('created_at')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse {
        $data = $this->validatePublication($request);
        $publish = (bool) ($data['publish'] ?? false);
        $validityDays = $data['validity_days'] ?? null;
        unset($data['publish'], $data['validity_days'], $data['preview_confirmed']);
        $data['user_id'] = $request->user()->id;
        $this->applyLifecycle($data, $publish, $validityDays);
        $need = Need::create($data);
        AuditLogger::log($request, 'need.saved', $need, $need->status, [
            'visibility' => $need->visibility,
            'status' => $need->status,
            'category_id' => $need->service_category_id,
        ]);
        $need->load('category:id,name');
        return response()->json($need, 201);
    }

    public function show(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        return response()->json($need->load(['user:id,name,last_name', 'category:id,name']));
    }

    public function update(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $data = $this->validatePublication($request);
        $publish = (bool) ($data['publish'] ?? false);
        $validityDays = $data['validity_days'] ?? null;
        unset($data['publish'], $data['validity_days'], $data['preview_confirmed']);
        $this->applyLifecycle($data, $publish, $validityDays);
        $need->update($data);
        AuditLogger::log($request, 'need.updated', $need, $need->status, [
            'visibility' => $need->visibility,
            'status' => $need->status,
            'category_id' => $need->service_category_id,
        ]);
        return response()->json($need->load('category:id,name'));
    }

    public function suspend(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $need->update(['status' => 'suspended']);
        AuditLogger::log($request, 'need.suspended', $need, 'suspended');
        return response()->json($need->fresh()->load('category:id,name'));
    }

    public function destroy(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $need->delete();
        AuditLogger::log($request, 'need.deleted', $need, 'deleted');
        return response()->json(null, 204);
    }

    private function validatePublication(Request $request): array {
        return $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'required|string',
            'service_category_id' => 'nullable|integer|exists:service_categories,id',
            'location'            => 'nullable|string|max:255',
            'urgency'             => 'nullable|string|in:low,medium,high',
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,registered,anonymous',
            'publish'             => 'boolean',
            'validity_days'       => 'nullable|integer|in:' . implode(',', self::ALLOWED_VALIDITY_DAYS) . '|required_if:publish,true',
            'preview_confirmed'   => 'exclude_unless:publish,true|required|accepted',
        ]);
    }

    /** Publicar activa la publicación con ventana de vigencia; guardar la deja como borrador. */
    private function applyLifecycle(array &$data, bool $publish, ?int $validityDays): void {
        if ($publish) {
            $now = now();
            $data['status'] = 'active';
            $data['published_at'] = $now;
            $data['expires_at'] = $now->copy()->addDays($validityDays);
        } else {
            $data['status'] = 'draft';
        }
    }

    private function applyStatusFilter($query, ?string $status): void {
        if ($status === null || $status === '') return;
        if ($status === 'expired') {
            $query->where('status', 'active')->where('expires_at', '<=', now());
        } elseif ($status === 'active') {
            $query->where('status', 'active')
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        } else {
            $query->where('status', $status);
        }
    }
}
