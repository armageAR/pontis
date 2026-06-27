<?php
namespace App\Http\Controllers;
use App\Models\UserDegree;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class DegreeController extends Controller {
    public function index(Request $request): JsonResponse {
        $degrees = $request->user()->userDegrees()->with('workshop:id,name,number')->orderByDesc('start_date')->get();
        return response()->json($degrees);
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'degree'      => 'required|string|in:aprendiz,companero,maestro',
            'workshop_id' => 'nullable|integer|exists:workshops,id',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after:start_date',
            'notes'       => 'nullable|string',
        ]);
        $data['user_id'] = $request->user()->id;
        $degree = UserDegree::create($data);
        $degree->load('workshop:id,name,number');
        return response()->json($degree, 201);
    }
    public function update(Request $request, UserDegree $degree): JsonResponse {
        abort_if($degree->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'degree'      => 'sometimes|string|in:aprendiz,companero,maestro',
            'workshop_id' => 'nullable|integer|exists:workshops,id',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);
        $degree->update($data);
        return response()->json($degree->load('workshop:id,name,number'));
    }
    public function destroy(Request $request, UserDegree $degree): JsonResponse {
        abort_if($degree->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $degree->delete();
        return response()->json(null, 204);
    }
}
