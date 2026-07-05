<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\PontisNotification;
use App\Models\Service;
use App\Models\User;
use App\Models\UserVisibilitySetting;
use App\Models\Workshop;
use App\Support\VisibilityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContactMediatedFlowTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => 'member', 'is_principal' => true]);
        return $user;
    }

    private function hideIdentity(User $user): void
    {
        UserVisibilitySetting::create([
            'user_id' => $user->id,
            'block' => 'identity',
            'visibility' => 'private',
            'anonymous_search' => true,
        ]);
    }

    private function service(User $owner): Service
    {
        return Service::create([
            'user_id' => $owner->id,
            'title' => 'Asesoramiento reservado',
            'description' => 'Acompañamiento profesional.',
            'modality' => 'both',
            'visibility' => 'anonymous',
            'status' => 'active',
            'published_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);
    }

    public function test_mediated_request_from_anonymous_publication_does_not_reveal_target_identity_or_id(): void
    {
        $owner = $this->member(Workshop::factory()->create());
        $owner->update(['name' => 'Juan', 'last_name' => 'Perez']);
        $this->hideIdentity($owner);
        $service = $this->service($owner);
        $requester = $this->member(Workshop::factory()->create());

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'service_id' => $service->id,
            'message' => 'Quisiera consultar por esta publicación.',
            'reason_type' => 'offer_publication',
            'source' => 'publications',
            'shared_fields' => ['identity', 'email'],
        ])->assertCreated()->json();

        $this->assertNull($created['requestee_id']);
        $this->assertSame(VisibilityPolicy::MASKED_NAME, $created['requestee']['name']);
        $this->assertNull($created['requestee']['last_name']);
        $this->assertSame($owner->id, ContactRequest::findOrFail($created['id'])->requestee_id);

        $sent = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/contact-requests?direction=sent')
            ->assertOk()
            ->json('data.0');

        $this->assertNull($sent['requestee_id']);
        $this->assertSame(VisibilityPolicy::MASKED_NAME, $sent['requestee']['name']);
        $this->assertSame($service->id, $sent['service_id']);
    }

    public function test_explore_anonymous_publication_does_not_serialize_owner_user_id(): void
    {
        $this->markTestSkipped('Publicaciones diferidas a V2 (defer-publications-to-v2).');
        $owner = $this->member(Workshop::factory()->create());
        $service = $this->service($owner);
        $requester = $this->member(Workshop::factory()->create());

        $row = collect($this->actingAs($requester, 'sanctum')
            ->getJson('/api/explore/services')
            ->assertOk()
            ->json('data'))
            ->firstWhere('id', $service->id);

        $this->assertNotNull($row);
        $this->assertNull($row['user_id']);
        $this->assertNull($row['user']['id']);
    }

    public function test_accepted_mediated_request_reveals_target_identity_after_consent(): void
    {
        $owner = $this->member(Workshop::factory()->create());
        $owner->update(['name' => 'Juan', 'last_name' => 'Perez']);
        $this->hideIdentity($owner);
        $service = $this->service($owner);
        $requester = $this->member(Workshop::factory()->create());

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'service_id' => $service->id,
            'message' => 'Quisiera consultar por esta publicación.',
            'reason_type' => 'offer_publication',
        ])->assertCreated()->json();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/accept")
            ->assertOk();

        $sent = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/contact-requests?direction=sent')
            ->assertOk()
            ->json('data.0');

        $this->assertSame($owner->id, $sent['requestee_id']);
        $this->assertSame('Juan', $sent['requestee']['name']);
    }

    public function test_rejected_mediated_request_reveals_no_additional_target_data(): void
    {
        $owner = $this->member(Workshop::factory()->create());
        $owner->update(['name' => 'Juan']);
        $this->hideIdentity($owner);
        $service = $this->service($owner);
        $requester = $this->member(Workshop::factory()->create());

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'service_id' => $service->id,
            'message' => 'Quisiera consultar por esta publicación.',
            'reason_type' => 'offer_publication',
        ])->assertCreated()->json();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/reject")
            ->assertOk();

        $notification = PontisNotification::where('user_id', $requester->id)
            ->where('type', 'contact_rejected')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString(VisibilityPolicy::MASKED_NAME, $notification->body);
        $this->assertStringNotContainsString('Juan', $notification->body);
    }

    public function test_recipient_can_request_more_information_without_revealing_identity(): void
    {
        $owner = $this->member(Workshop::factory()->create());
        $owner->update(['name' => 'Juan']);
        $this->hideIdentity($owner);
        $service = $this->service($owner);
        $requester = $this->member(Workshop::factory()->create());

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'service_id' => $service->id,
            'message' => 'Quisiera consultar por esta publicación.',
            'reason_type' => 'offer_publication',
        ])->assertCreated()->json();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/request-info", [
                'response_message' => '¿Podés ampliar el motivo de contacto?',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'info_requested');

        $notification = PontisNotification::where('user_id', $requester->id)
            ->where('type', 'contact_info_requested')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString(VisibilityPolicy::MASKED_NAME, $notification->body);
        $this->assertStringNotContainsString('Juan', $notification->body);
    }
}
