<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserVisibilitySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProfileController extends Controller {
    // Levels ordered from most restrictive to least
    private const LEVELS = ['private','workshop','my_workshops','registered','anonymous'];

    public function show(Request $request, User $user): JsonResponse {
        $viewer = $request->user();
        $settings = UserVisibilitySetting::where('user_id', $user->id)->get()->keyBy('block');

        // Determine viewer's relationship to the profile owner
        $viewerLevel = $this->resolveViewerLevel($viewer, $user);

        $can = fn(string $block, string $default = 'workshop') =>
            $this->canSee($viewerLevel, $settings[$block]->visibility ?? $default);

        $user->load(['workshops:id,name,number','userDegrees.workshop:id,name,number','userPositions.position:id,name','userPositions.workshop:id,name,number']);

        $data = ['id' => $user->id, 'name' => $user->name, 'workshops' => $user->workshops];

        if ($can('identity'))    { $data['last_name'] = $user->last_name; $data['masonic_id'] = $user->masonic_id; }
        if ($can('masonic'))     { $data['masonic_status'] = $user->masonic_status; $data['initiation_date'] = $user->initiation_date; }
        if ($can('contact'))     { $data['phone'] = $user->phone; $data['whatsapp'] = $user->whatsapp; $data['alternative_email'] = $user->alternative_email; }
        if ($can('location'))    { $data['province'] = $user->province; $data['locality'] = $user->locality; }
        if ($can('profession'))  { $data['profession'] = $user->profession; $data['occupation'] = $user->occupation; $data['company'] = $user->company; }
        if ($can('bio'))         { $data['bio'] = $user->bio; }
        if ($can('degrees'))     { $data['degrees'] = $user->userDegrees; }
        if ($can('positions'))   { $data['positions'] = $user->userPositions; }

        return response()->json($data);
    }

    private function resolveViewerLevel(User $viewer, User $subject): string {
        if ($viewer->id === $subject->id || $viewer->isSuperAdmin()) return 'anonymous';
        $viewerWorkshopIds = $viewer->workshops()->pluck('workshops.id')->toArray();
        $subjectWorkshopIds = $subject->workshops()->pluck('workshops.id')->toArray();
        if (!empty(array_intersect($viewerWorkshopIds, $subjectWorkshopIds))) {
            // Same workshop — count as workshop level
            return 'workshop';
        }
        if (!empty($viewerWorkshopIds)) return 'my_workshops';
        return 'registered';
    }

    private function canSee(string $viewerLevel, string $requiredLevel): bool {
        $vIdx = array_search($viewerLevel, self::LEVELS);
        $rIdx = array_search($requiredLevel, self::LEVELS);
        // viewer index >= required index means the viewer has sufficient access
        return $vIdx >= $rIdx;
    }
}
