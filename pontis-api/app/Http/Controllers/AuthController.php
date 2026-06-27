<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'last_name'   => 'nullable|string|max:255',
            'email'       => 'required|email|unique:users',
            'password'    => 'required|string|min:8|confirmed',
            'dni'         => 'nullable|string|max:20',
            'masonic_id'  => 'nullable|string|max:50',
            'workshop_id' => 'required|integer|exists:workshops,id',
        ]);

        $workshopId = $data['workshop_id'];
        unset($data['workshop_id']);

        $data['status'] = UserStatus::PENDING->value;

        $user = User::create($data);
        $user->workshops()->attach($workshopId);

        // Notify workshop admins about new pending member
        $workshop = \App\Models\Workshop::find($workshopId);
        $admins = \App\Models\User::whereHas('workshopMemberships', function ($q) use ($workshopId) {
            $q->where('user_workshop.workshop_id', $workshopId)->where('user_workshop.role', 'admin')->where('user_workshop.status', 'active');
        })->get();
        foreach ($admins as $admin) {
            \App\Models\PontisNotification::create([
                'user_id' => $admin->id,
                'type'    => 'new_member_pending',
                'title'   => 'Nuevo miembro pendiente',
                'body'    => "{$user->name}" . ($user->last_name ? " {$user->last_name}" : '') . " solicitó unirse al taller \"{$workshop->name}\".",
                'data'    => ['user_id' => $user->id, 'workshop_id' => $workshopId],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        event(new Registered($user));

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ], 201);
    }

    public function searchWorkshops(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $q = mb_strtolower($request->input('q'));

        $workshops = Workshop::query()
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) like ?', ["%{$q}%"])
                      ->orWhereRaw("CAST(number AS TEXT) like ?", ["%{$q}%"]);
            })
            ->orderBy('number')
            ->limit(10)
            ->get(['id', 'name', 'number', 'zone_name', 'city']);

        return response()->json($workshops);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if ($user->status === UserStatus::REJECTED) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta fue rechazada. Contactá al administrador.'],
            ]);
        }

        if ($user->status === UserStatus::INACTIVE) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta fue dada de baja.'],
            ]);
        }

        if ($user->status === UserStatus::SUSPENDED) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está suspendida. Contactá al administrador.'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    public function accountStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $memberships = $user->workshopMemberships()
            ->withPivot('status', 'correction_notes')
            ->get(['workshops.id','workshops.name','workshops.number'])
            ->map(fn($w) => [
                'workshop_id'      => $w->id,
                'workshop_name'    => $w->name,
                'workshop_number'  => $w->number,
                'status'           => $w->pivot->status,
                'correction_notes' => $w->pivot->correction_notes,
            ]);

        return response()->json([
            'status'               => $user->status,
            'email_verified'       => $user->hasVerifiedEmail(),
            'email_verified_at'    => $user->email_verified_at,
            'verification_sent_at' => $user->created_at,
            'memberships'          => $memberships,
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'El email ya fue verificado.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email de verificación reenviado.']);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'El link de verificación expiró o es inválido.'], 403);
        }

        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'Link de verificación inválido.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json(['message' => 'Email verificado correctamente.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'role'              => $user->role,
            'status'            => $user->status,
            'email_verified_at' => $user->email_verified_at,
        ];
    }
}
