<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserVisibilitySetting;
use App\Support\ProfileVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProfileController extends Controller {
    public function show(Request $request, User $user): JsonResponse {
        $viewer = $request->user();
        $settings = UserVisibilitySetting::where('user_id', $user->id)->get()->keyBy('block');

        $user->load([
            'workshops:id,name,number',
            'userDegrees.workshop:id,name,number',
            'userPositions.position:id,name',
            'userPositions.workshop:id,name,number',
        ]);

        // Misma resolución de visibilidad que la búsqueda de Hermanos.
        $vis = new ProfileVisibility($viewer);
        $workshopIds = $user->workshops->pluck('id')->all();
        $principalId = optional($user->workshops->first(fn($w) => (bool) ($w->pivot->is_principal ?? false)))->id;

        $level = fn(string $block) => optional($settings->get($block))->visibility ?? 'workshop';
        $can   = fn(string $block) => $vis->canSee($level($block), $user->id, $workshopIds, $principalId);

        $isSelf = $viewer->id === $user->id;
        $idLevel = $level('identity');
        // Identidad anónima: el nombre no se revela (salvo a uno mismo).
        $identityVisible = $isSelf || ($idLevel !== 'anonymous' && $can('identity'));

        $data = [
            'id'        => $user->id,
            'name'      => $identityVisible ? $user->name : 'Hermano registrado',
            'anonymous' => ! $identityVisible,
            'workshops' => $user->workshops, // el taller es información institucional
        ];

        if ($identityVisible)   { $data['last_name'] = $user->last_name; $data['masonic_id'] = $user->masonic_id; }
        if ($can('masonic'))    { $data['masonic_status'] = $user->masonic_status; $data['initiation_date'] = $user->initiation_date; }
        if ($can('contact'))    { $data['phone'] = $user->phone; $data['whatsapp'] = $user->whatsapp; $data['alternative_email'] = $user->alternative_email; }
        if ($can('location'))   { $data['province'] = $user->province; $data['locality'] = $user->locality; }
        if ($can('profession')) { $data['profession'] = $user->profession; $data['occupation'] = $user->occupation; $data['company'] = $user->company; }
        if ($can('bio'))        { $data['bio'] = $user->bio; }
        if ($can('degrees'))    { $data['degrees'] = $user->userDegrees; }
        if ($can('positions'))  { $data['positions'] = $user->userPositions; }

        return response()->json($data);
    }
}
