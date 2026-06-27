<?php
namespace App\Http\Controllers;
use App\Models\Need;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class NeedController extends Controller {
    public function index(Request $request): JsonResponse {
        $query = $request->user()->isSuperAdmin()
            ? Need::with(['user:id,name,last_name','category:id,name'])
            : Need::where('user_id', $request->user()->id)->with(['category:id,name']);
        if ($request->filled('status')) $query->where('status', $request->status);
        $needs = $query->orderByDesc('created_at')->paginate(20);
        return response()->json($needs);
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'service_category_id' => 'nullable|integer|exists:service_categories,id',
            'location'            => 'nullable|string|max:255',
            'urgency'             => 'nullable|string|in:low,medium,high',
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,registered,anonymous',
            'status'              => 'nullable|string|in:draft,open,searching,with_matches,contact_requested,linked,closed,cancelled',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['status'] ??= 'draft';
        $need = Need::create($data);
        $need->load('category:id,name');
        return response()->json($need, 201);
    }
    public function show(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        return response()->json($need->load(['user:id,name,last_name','category:id,name']));
    }
    public function update(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'title'               => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'service_category_id' => 'nullable|integer|exists:service_categories,id',
            'location'            => 'nullable|string|max:255',
            'urgency'             => 'nullable|string|in:low,medium,high',
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,registered,anonymous',
            'status'              => 'nullable|string|in:draft,open,searching,with_matches,contact_requested,linked,closed,cancelled',
        ]);
        $need->update($data);
        return response()->json($need->load('category:id,name'));
    }
    public function destroy(Request $request, Need $need): JsonResponse {
        abort_if($need->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $need->delete();
        return response()->json(null, 204);
    }
}
