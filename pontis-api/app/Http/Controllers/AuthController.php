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
            'email'       => 'required|email|unique:users',
            'password'    => 'required|string|min:8|confirmed',
            'workshop_id' => 'required|integer|exists:workshops,id',
        ]);

        $workshopId = $data['workshop_id'];
        unset($data['workshop_id']);

        $data['status'] = UserStatus::PENDING->value;

        $user = User::create($data);
        $user->workshops()->attach($workshopId);

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

        return response()->json([
            'status'              => $user->status,
            'email_verified'      => $user->hasVerifiedEmail(),
            'email_verified_at'   => $user->email_verified_at,
            'verification_sent_at' => $user->email_verified_at ?? $user->created_at,
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
