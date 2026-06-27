<?php
namespace App\Http\Controllers;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ServiceCategoryController extends Controller {
    public function index(): JsonResponse {
        return response()->json(ServiceCategory::where('active', true)->orderBy('name')->get());
    }
    public function store(Request $request): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name'=>'required|string|max:100|unique:service_categories,name','description'=>'nullable|string']);
        return response()->json(ServiceCategory::create($data), 201);
    }
    public function update(Request $request, ServiceCategory $serviceCategory): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name'=>'sometimes|string|max:100','description'=>'nullable|string','active'=>'boolean']);
        $serviceCategory->update($data);
        return response()->json($serviceCategory);
    }
    public function destroy(Request $request, ServiceCategory $serviceCategory): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $serviceCategory->delete();
        return response()->json(null, 204);
    }
}
