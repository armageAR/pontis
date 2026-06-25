<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlaSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkshopSyncController extends Controller
{
    public function __construct(private GlaSyncService $sync) {}

    public function preview(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'superadmin', 403);

        try {
            $diff = $this->sync->preview();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json($diff);
    }

    public function apply(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'superadmin', 403);

        $validated = $request->validate([
            'new'        => ['present', 'array'],
            'new.*'      => ['integer'],
            'modified'   => ['present', 'array'],
            'modified.*' => ['integer'],
            'disabled'   => ['present', 'array'],
            'disabled.*' => ['integer'],
        ]);

        try {
            $created  = empty($validated['new'])      ? 0 : $this->sync->applyNew($validated['new']);
            $updated  = empty($validated['modified'])  ? 0 : $this->sync->applyModified($validated['modified']);
            $disabled = empty($validated['disabled'])  ? 0 : $this->sync->applyDisabled($validated['disabled']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'message' => 'Sincronización aplicada.',
            'counts'  => compact('created', 'updated', 'disabled'),
        ]);
    }
}
