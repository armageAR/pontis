<?php
namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ProfileController extends Controller {
    public function show(Request $request): JsonResponse {
        $user = $request->user()->load(['workshops','userDegrees.workshop','userPositions.position','userPositions.workshop']);
        return response()->json($user);
    }
    public function update(Request $request): JsonResponse {
        $user = $request->user();
        $data = $request->validate([
            'name'                   => 'sometimes|string|max:255',
            'last_name'              => 'nullable|string|max:255',
            'dni'                    => 'nullable|string|max:20',
            'masonic_id'             => 'nullable|string|max:50',
            'birth_date'             => 'nullable|date',
            'initiation_date'        => 'nullable|date',
            'masonic_status'         => 'nullable|string|in:active,inactive,suspended,discharged,deceased',
            'phone'                  => 'nullable|string|max:30',
            'phone_fixed'            => 'nullable|string|max:30',
            'whatsapp'               => 'nullable|string|max:30',
            'alternative_email'      => 'nullable|email',
            'contact_preference'     => 'nullable|string|max:50',
            'country'                => 'nullable|string|max:100',
            'province'               => 'nullable|string|max:100',
            'locality'               => 'nullable|string|max:100',
            'neighborhood'           => 'nullable|string|max:100',
            'address'                => 'nullable|string|max:255',
            'profession'             => 'nullable|string|max:255',
            'occupation'             => 'nullable|string|max:255',
            'company'                => 'nullable|string|max:255',
            'profession_description' => 'nullable|string',
            'secondary_activities'   => 'nullable|string',
            'knowledge_areas'        => 'nullable|string',
            'certifications'         => 'nullable|string',
            'bio'                    => 'nullable|string',
            'photo_url'              => 'nullable|url|max:512',
            'linkedin'               => 'nullable|url|max:255',
            'website'                => 'nullable|url|max:255',
            'facebook'               => 'nullable|string|max:255',
            'instagram'              => 'nullable|string|max:255',
            'availability_notes'     => 'nullable|string|max:500',
        ]);
        $user->update($data);
        return response()->json($user->fresh());
    }
}
