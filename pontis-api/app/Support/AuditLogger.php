<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(Request $request, string $action, Model|string $entity, ?string $decision = null, array $metadata = []): AuditLog
    {
        $type = is_string($entity) ? $entity : $entity::class;
        $id = is_string($entity) ? null : $entity->getKey();

        return AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => class_basename($type),
            'entity_id' => $id,
            'decision' => $decision,
            'metadata' => self::sanitizeMetadata($metadata),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
    }

    private static function sanitizeMetadata(array $metadata): array
    {
        unset($metadata['message'], $metadata['reason'], $metadata['new_value'], $metadata['current_value'], $metadata['password'], $metadata['response_message']);
        return $metadata;
    }
}
