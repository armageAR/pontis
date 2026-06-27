<?php
namespace App\Http\Controllers;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class PositionCatalogController extends Controller {
    public function index(): JsonResponse {
        return response()->json(Position::where('active', true)->orderBy('name')->get());
    }
    public function store(Request $request): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name'=>'required|string|max:100|unique:positions,name','description'=>'nullable|string','active'=>'boolean']);
        return response()->json(Position::create($data), 201);
    }
    public function update(Request $request, Position $position): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name'=>'sometimes|string|max:100','description'=>'nullable|string','active'=>'boolean']);
        $position->update($data);
        return response()->json($position);
    }
    public function destroy(Request $request, Position $position): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $position->delete();
        return response()->json(null, 204);
    }
}
