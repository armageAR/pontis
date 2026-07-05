<?php
namespace App\Http\Controllers;
use App\Notifications\VerifyEmailChangeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
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
        // Datos sensibles de identidad: sólo el Superadmin los cambia directo.
        // Para el resto se descartan; el cambio se gestiona como trámite validable
        // (ChangeRequest), no por edición directa del perfil.
        if (! $user->isSuperAdmin()) {
            unset($data['name'], $data['last_name'], $data['dni'], $data['masonic_id']);
        }
        $user->update($data);
        return response()->json($user->fresh());
    }

    // El usuario solicita cambiar su propio email. No se aplica de inmediato:
    // se guarda como pending_email y se envía un correo de confirmación a la
    // nueva dirección. El cambio recién se aplica al confirmar (confirmEmailChange).
    public function requestEmailChange(Request $request): JsonResponse {
        $user = $request->user();
        $data = $request->validate([
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $newEmail = mb_strtolower(trim($data['email']));

        if ($newEmail === mb_strtolower($user->email)) {
            return response()->json(['message' => 'El email nuevo es igual al actual.'], 422);
        }

        $user->pending_email = $newEmail;
        $user->save();

        Notification::route('mail', $newEmail)
            ->notify(new VerifyEmailChangeNotification($user));

        return response()->json([
            'message'       => 'Te enviamos un correo a la nueva dirección. Confirmá desde ahí para aplicar el cambio.',
            'pending_email' => $newEmail,
        ]);
    }
}
