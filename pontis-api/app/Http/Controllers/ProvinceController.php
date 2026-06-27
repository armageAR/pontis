<?php
namespace App\Http\Controllers;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
class ProvinceController extends Controller {
    public function index(): JsonResponse {
        return response()->json(Province::orderBy('name')->get(['id','name','code']));
    }
}
