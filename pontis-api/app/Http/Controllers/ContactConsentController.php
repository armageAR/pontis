<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactConsentController extends Controller
{
    public const ALLOWED_SHARED_FIELDS = ['identity', 'email', 'phone', 'whatsapp', 'workshop', 'profession'];
    public const ALLOWED_CHANNELS = ['email', 'phone', 'whatsapp', 'in_flow'];
    public const ALLOWED_SOURCES = ['search', 'publications'];

    public static function defaults(): array
    {
        return [
            'default_shared_fields' => ['identity'],
            'preferred_channels' => ['in_flow'],
            'allowed_sources' => ['search', 'publications'],
        ];
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'default_shared_fields' => 'required|array',
            'default_shared_fields.*' => 'string|in:' . implode(',', self::ALLOWED_SHARED_FIELDS),
            'preferred_channels' => 'required|array',
            'preferred_channels.*' => 'string|in:' . implode(',', self::ALLOWED_CHANNELS),
            'allowed_sources' => 'required|array',
            'allowed_sources.*' => 'string|in:' . implode(',', self::ALLOWED_SOURCES),
        ]);

        $user = $request->user();
        $user->contact_default_shared_fields = $this->normalize($data['default_shared_fields'], self::ALLOWED_SHARED_FIELDS);
        $user->contact_preferred_channels = $this->normalize($data['preferred_channels'], self::ALLOWED_CHANNELS);
        $user->contact_allowed_sources = $this->normalize($data['allowed_sources'], self::ALLOWED_SOURCES);
        $user->save();

        return response()->json($this->payload($user->fresh()));
    }

    public static function sourceAllowed($user, string $source): bool
    {
        $allowed = $user->contact_allowed_sources ?? self::defaults()['allowed_sources'];
        return in_array($source, $allowed, true);
    }

    public static function defaultSharedFields($user): array
    {
        return $user->contact_default_shared_fields ?? self::defaults()['default_shared_fields'];
    }

    private function payload($user): array
    {
        return [
            'default_shared_fields' => $user->contact_default_shared_fields ?? self::defaults()['default_shared_fields'],
            'preferred_channels' => $user->contact_preferred_channels ?? self::defaults()['preferred_channels'],
            'allowed_sources' => $user->contact_allowed_sources ?? self::defaults()['allowed_sources'],
        ];
    }

    private function normalize(array $values, array $allowed): array
    {
        return collect($values)
            ->filter(fn ($value) => in_array($value, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }
}
