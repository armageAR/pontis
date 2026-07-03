<?php

namespace Tests\Feature;

use App\Models\PontisNotification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactRequestSharingTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => 'member', 'is_principal' => true]);
        return $user;
    }

    public function test_unshared_requester_fields_are_absent_from_received_request(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requester->update(['phone' => '111', 'whatsapp' => '222', 'profession' => 'Abogado']);
        $requestee = $this->member($workshop);

        $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message' => 'Necesito contactarte.',
            'reason_type' => 'other',
            'shared_fields' => ['identity', 'email'],
        ])->assertCreated();

        $row = $this->actingAs($requestee, 'sanctum')
            ->getJson('/api/contact-requests?direction=received')
            ->assertOk()
            ->json('data.0.requester');

        $this->assertSame($requester->name, $row['name']);
        $this->assertSame($requester->email, $row['email']);
        $this->assertArrayNotHasKey('phone', $row);
        $this->assertArrayNotHasKey('whatsapp', $row);
        $this->assertArrayNotHasKey('profession', $row);
    }

    public function test_identity_reserved_request_uses_neutral_notification(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requestee = $this->member($workshop);

        $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message' => 'Necesito contactarte.',
            'reason_type' => 'other',
            'shared_fields' => ['email'],
        ])->assertCreated();

        $notification = PontisNotification::where('user_id', $requestee->id)
            ->where('type', 'contact_received')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Hermano registrado', $notification->body);
        $this->assertStringNotContainsString($requester->name, $notification->body);
        $this->assertArrayNotHasKey('requester_name', $notification->data ?? []);

        $row = $this->actingAs($requestee, 'sanctum')
            ->getJson('/api/contact-requests?direction=received')
            ->assertOk()
            ->json('data.0.requester');

        $this->assertSame('Hermano registrado', $row['name']);
        $this->assertTrue($row['anonymous']);
        $this->assertSame($requester->email, $row['email']);
    }

    public function test_accept_reveals_only_requester_shared_fields_to_recipient(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requester->update(['phone' => '111', 'whatsapp' => '222']);
        $requestee = $this->member($workshop);

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message' => 'Necesito contactarte.',
            'reason_type' => 'other',
            'shared_fields' => ['identity', 'phone'],
        ])->assertCreated()->json();

        $accepted = $this->actingAs($requestee, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/accept")
            ->assertOk()
            ->json('requester');

        $this->assertSame($requester->name, $accepted['name']);
        $this->assertSame('111', $accepted['phone']);
        $this->assertArrayNotHasKey('email', $accepted);
        $this->assertArrayNotHasKey('whatsapp', $accepted);
    }
}
