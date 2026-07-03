<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactReasonTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => 'member', 'is_principal' => true]);
        return $user;
    }

    public function test_contact_request_requires_reason(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requestee = $this->member($workshop);

        $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message' => 'Necesito contactarte.',
        ])->assertUnprocessable()->assertJsonValidationErrors(['reason_type']);
    }

    public function test_contact_request_requires_meaningful_message(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requestee = $this->member($workshop);

        $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message' => 'Hola',
            'reason_type' => 'other',
        ])->assertUnprocessable()->assertJsonValidationErrors(['message']);
    }

    public function test_contact_request_preserves_reason_and_source_context(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requestee = $this->member($workshop);

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message' => 'Necesito contactarte por tu oficio.',
            'reason_type' => 'profession_search',
            'source_context' => ['q' => 'carpintero'],
        ])->assertCreated()->json();

        $this->assertSame('profession_search', $created['reason_type']);
        $this->assertSame(['q' => 'carpintero'], $created['source_context']);

        $received = $this->actingAs($requestee, 'sanctum')
            ->getJson('/api/contact-requests?direction=received')
            ->assertOk()
            ->json('data.0');

        $this->assertSame('profession_search', $received['reason_type']);
        $this->assertSame(['q' => 'carpintero'], $received['source_context']);
    }
}
