<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunctionalAuditTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'status' => 'active'], $overrides));
    }

    public function test_superadmin_can_query_audit_and_regular_user_cannot(): void
    {
        $superadmin = $this->user(['role' => 'superadmin']);
        $regular = $this->user();

        $this->actingAs($regular, 'sanctum')->getJson('/api/audit-logs')->assertForbidden();
        $this->actingAs($superadmin, 'sanctum')->getJson('/api/audit-logs')->assertOk();
    }

    public function test_user_status_change_creates_audit_log(): void
    {
        $superadmin = $this->user(['role' => 'superadmin']);
        $target = $this->user(['status' => 'pending']);

        $this->actingAs($superadmin, 'sanctum')
            ->patchJson("/api/users/{$target->id}/status", ['status' => 'active'])
            ->assertOk();

        $log = AuditLog::where('action', 'user.status_changed')->first();

        $this->assertNotNull($log);
        $this->assertSame($superadmin->id, $log->actor_id);
        $this->assertSame('User', $log->entity_type);
        $this->assertSame($target->id, $log->entity_id);
        $this->assertSame('active', $log->decision);
        $this->assertSame('pending', $log->metadata['old_status']);
    }

    public function test_contact_resolution_audit_does_not_store_private_message_content(): void
    {
        $requester = $this->user();
        $requestee = $this->user();
        $contactRequest = ContactRequest::create([
            'requester_id' => $requester->id,
            'requestee_id' => $requestee->id,
            'message' => 'Contenido privado de la solicitud',
            'reason_type' => 'other',
            'shared_fields' => ['identity'],
            'status' => 'pending',
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($requestee, 'sanctum')
            ->postJson("/api/contact-requests/{$contactRequest->id}/accept", [
                'response_message' => 'Respuesta privada',
            ])
            ->assertOk();

        $log = AuditLog::where('action', 'contact_request.accepted')->first();

        $this->assertNotNull($log);
        $this->assertSame($contactRequest->id, $log->entity_id);
        $this->assertStringNotContainsString('Contenido privado', json_encode($log->metadata));
        $this->assertStringNotContainsString('Respuesta privada', json_encode($log->metadata));
    }
}
