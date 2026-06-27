<?php
namespace App\Http\Controllers;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ServiceController extends Controller {
    public function index(Request $request): JsonResponse {
        $query = $request->user()->isSuperAdmin()
            ? Service::with(['user:id,name,last_name','category:id,name'])
            : Service::where('user_id', $request->user()->id)->with(['category:id,name']);
        if ($request->filled('status')) $query->where('status', $request->status);
        $services = $query->orderByDesc('created_at')->paginate(20);
        return response()->json($services);
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
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,registered,anonymous',
            'status'              => 'nullable|string|in:draft,active,paused,hidden,disabled',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['status'] ??= 'draft';
        $service = Service::create($data);
        $service->load('category:id,name');
        return response()->json($service, 201);
    }
    public function show(Request $request, Service $service): JsonResponse {
        abort_if($service->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        return response()->json($service->load(['user:id,name,last_name','category:id,name']));
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
            'visibility'          => 'nullable|string|in:private,workshop,my_workshops,registered,anonymous',
            'status'              => 'nullable|string|in:draft,active,paused,hidden,disabled',
        ]);
        $service->update($data);
        return response()->json($service->load('category:id,name'));
    }
    public function destroy(Request $request, Service $service): JsonResponse {
        abort_if($service->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $service->delete();
        return response()->json(null, 204);
    }
}
