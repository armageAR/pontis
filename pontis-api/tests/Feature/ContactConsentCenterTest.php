<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactConsentCenterTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => 'member', 'is_principal' => true]);
        return $user;
    }

    public function test_user_can_save_and_load_contact_consent_settings(): void
    {
        $user = $this->member(Workshop::factory()->create());

        $payload = [
            'default_shared_fields' => ['identity', 'phone', 'profession'],
            'preferred_channels' => ['phone', 'in_flow'],
            'allowed_sources' => ['publications'],
        ];

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile/contact-consent', $payload)
            ->assertOk()
            ->assertJson($payload);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile/contact-consent')
            ->assertOk()
            ->assertJson($payload);
    }

    public function test_contact_request_uses_saved_defaults_when_shared_fields_are_omitted(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requester->update(['contact_default_shared_fields' => ['identity', 'phone'], 'phone' => '123']);
        $requestee = $this->member($workshop);

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, necesito contactarte.',
            'reason_type'  => 'other',
        ])->assertCreated()->json();

        $this->assertSame(['identity', 'phone'], $created['shared_fields']);
    }

    public function test_contact_request_can_override_saved_defaults(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requester->update(['contact_default_shared_fields' => ['identity', 'phone'], 'phone' => '123']);
        $requestee = $this->member($workshop);

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, necesito contactarte.',
            'reason_type'  => 'other',
            'shared_fields' => ['email'],
        ])->assertCreated()->json();

        $this->assertSame(['email'], $created['shared_fields']);
    }

    public function test_disabled_search_source_rejects_contact_from_profile_search(): void
    {
        $workshop = Workshop::factory()->create();
        $requester = $this->member($workshop);
        $requestee = $this->member($workshop);
        $requestee->update(['contact_allowed_sources' => ['publications']]);

        $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, necesito contactarte.',
            'reason_type'  => 'other',
            'source' => 'search',
        ])->assertUnprocessable();
    }
}
