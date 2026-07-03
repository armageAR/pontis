<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'action' => 'nullable|string|max:120',
            'actor_id' => 'nullable|integer|exists:users,id',
            'entity_type' => 'nullable|string|max:120',
            'entity_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = AuditLog::with('actor:id,name,last_name,email')->latest();
        foreach (['action', 'actor_id', 'entity_type', 'entity_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $data[$field]);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $data['date_from']);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $data['date_to']);
        }

        return response()->json($query->paginate($data['per_page'] ?? 20));
    }
}
