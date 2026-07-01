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
            // La audiencia solo admite niveles de visibilidad. La aparición sin
            // revelar identidad se guarda por separado en "anonymous_search".
            'settings.*.visibility' => 'required|string|in:private,workshop,my_workshops,registered',
            'settings.*.anonymous_search' => 'sometimes|boolean',
        ]);
        $userId = $request->user()->id;
        foreach ($data['settings'] as $s) {
            $values = ['visibility' => $s['visibility']];
            // El flag de aparición anónima es propio del bloque Identidad y solo
            // se actualiza cuando el cliente lo envía, para no pisar el otro control.
            if ($s['block'] === 'identity' && array_key_exists('anonymous_search', $s)) {
                $values['anonymous_search'] = (bool) $s['anonymous_search'];
            }
            UserVisibilitySetting::updateOrCreate(
                ['user_id' => $userId, 'block' => $s['block']],
                $values
            );
        }
        $settings = UserVisibilitySetting::where('user_id', $userId)->get()->keyBy('block');
        return response()->json($settings);
    }
}
