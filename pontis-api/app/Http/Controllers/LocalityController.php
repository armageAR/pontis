<?php
namespace App\Http\Controllers;
use App\Models\Locality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class LocalityController extends Controller {
    public function index(Request $request): JsonResponse {
        $query = Locality::orderBy('name');
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }
        return response()->json($query->get(['id','province_id','name']));
    }
}
