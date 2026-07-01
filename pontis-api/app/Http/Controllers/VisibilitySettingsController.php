<?php
namespace App\Http\Controllers;
use App\Models\UserVisibilitySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class VisibilitySettingsController extends Controller {
    public function index(Request $request): JsonResponse {
        $settings = UserVisibilitySetting::where('user_id', $request->user()->id)->get()->keyBy('block');
        return response()->json($settings);
    }
    public function update(Request $request): JsonResponse {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.block' => 'required|string|in:identity,masonic,contact,location,profession,bio,degrees,positions',
            'settings.*.visibility' => 'required|string|in:private,workshop,my_workshops,registered,anonymous',
        ]);
        $userId = $request->user()->id;
        foreach ($data['settings'] as $s) {
            UserVisibilitySetting::updateOrCreate(
                ['user_id' => $userId, 'block' => $s['block']],
                ['visibility' => $s['visibility']]
            );
        }
        $settings = UserVisibilitySetting::where('user_id', $userId)->get()->keyBy('block');
        return response()->json($settings);
    }
}
