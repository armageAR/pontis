<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Support\VisibilityPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProfileController extends Controller {
    public function show(Request $request, User $user): JsonResponse {
        // Toda la resolución de visibilidad vive en la política central.
        $policy = new VisibilityPolicy($request->user());
        return response()->json($policy->profilePayload($user));
    }
}
