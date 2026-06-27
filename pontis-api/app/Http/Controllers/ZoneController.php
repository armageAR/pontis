<?php
namespace App\Http\Controllers;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ZoneController extends Controller {
    public function index(): JsonResponse {
        return response()->json(Zone::where('active', true)->orderBy('name')->get(['id','name','description']));
    }
    public function store(Request $request): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name'=>'required|string|max:100|unique:zones','description'=>'nullable|string|max:255']);
        return response()->json(Zone::create($data), 201);
    }
    public function update(Request $request, Zone $zone): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name'=>'sometimes|string|max:100|unique:zones,name,'.$zone->id,'description'=>'nullable|string|max:255','active'=>'sometimes|boolean']);
        $zone->update($data);
        return response()->json($zone);
    }
    public function destroy(Request $request, Zone $zone): JsonResponse {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $zone->update(['active' => false]);
        return response()->json(null, 204);
    }
}
