<?php
namespace App\Http\Controllers;
use App\Models\UserPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class UserPositionController extends Controller {
    public function index(Request $request): JsonResponse {
        $positions = $request->user()->userPositions()->with(['position:id,name','workshop:id,name,number'])->orderByDesc('start_date')->get();
        return response()->json($positions);
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'position_id' => 'required|integer|exists:positions,id',
            'workshop_id' => 'required|integer|exists:workshops,id',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after:start_date',
            'notes'       => 'nullable|string',
        ]);
        $data['user_id'] = $request->user()->id;
        $up = UserPosition::create($data);
        $up->load(['position:id,name','workshop:id,name,number']);
        return response()->json($up, 201);
    }
    public function update(Request $request, UserPosition $userPosition): JsonResponse {
        abort_if($userPosition->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'position_id' => 'sometimes|integer|exists:positions,id',
            'workshop_id' => 'sometimes|integer|exists:workshops,id',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);
        $userPosition->update($data);
        return response()->json($userPosition->load(['position:id,name','workshop:id,name,number']));
    }
    public function destroy(Request $request, UserPosition $userPosition): JsonResponse {
        abort_if($userPosition->user_id !== $request->user()->id && !$request->user()->isSuperAdmin(), 403);
        $userPosition->delete();
        return response()->json(null, 204);
    }
}
