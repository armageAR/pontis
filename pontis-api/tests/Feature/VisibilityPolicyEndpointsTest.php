<?php

namespace Tests\Feature;

use App\Models\PontisNotification;
use App\Models\Service;
use App\Models\User;
use App\Models\UserVisibilitySetting;
use App\Models\Workshop;
use App\Support\VisibilityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisibilityPolicyEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, bool $principal = true): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => 'member', 'is_principal' => $principal]);
        return $user;
    }

    private function setBlock(User $user, string $block, string $visibility, bool $anonymousSearch = false): void
    {
        UserVisibilitySetting::create([
            'user_id'          => $user->id,
            'block'            => $block,
            'visibility'       => $visibility,
            'anonymous_search' => $anonymousSearch,
        ]);
    }

    private function activeService(User $owner, string $visibility, string $title = 'Servicio'): Service
    {
        return Service::create([
            'user_id'      => $owner->id,
            'title'        => $title,
            'description'  => 'Desc',
            'modality'     => 'both',
            'visibility'   => $visibility,
            'status'       => 'active',
            'published_at' => Carbon::now()->subDay(),
            'expires_at'   => Carbon::now()->addDays(30),
        ]);
    }

    // ── 2.2 Paridad del perfil público por bloques ───────────────────────────

    public function test_profile_blocks_hidden_by_default_for_unrelated_viewer(): void
    {
        $subject = $this->member(Workshop::factory()->create());
        $subject->update(['phone' => '1234', 'profession' => 'Abogado']);
        $viewer = $this->member(Workshop::factory()->create());

        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/people/{$subject->id}")->assertOk()->json();

        // Default 'workshop': un viewer sin relación no ve contacto ni profesión,
        // y la identidad aparece enmascarada.
        $this->assertArrayNotHasKey('phone', $res);
        $this->assertArrayNotHasKey('profession', $res);
        $this->assertSame(VisibilityPolicy::MASKED_NAME, $res['name']);
        $this->assertTrue($res['anonymous']);
    }

    public function test_profile_blocks_visible_when_registered_level(): void
    {
        $subject = $this->member(Workshop::factory()->create());
        $subject->update(['name' => 'Juan', 'last_name' => 'Perez', 'phone' => '1234', 'profession' => 'Abogado']);
        $this->setBlock($subject, 'identity', 'registered');
        $this->setBlock($subject, 'contact', 'registered');
        $this->setBlock($subject, 'profession', 'registered');
        $viewer = $this->member(Workshop::factory()->create());

        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/people/{$subject->id}")->assertOk()->json();

        $this->assertSame('Juan', $res['name']);
        $this->assertSame('1234', $res['phone']);
        $this->assertSame('Abogado', $res['profession']);
        $this->assertFalse($res['anonymous']);
    }

    // ── Alcance de publicaciones en explore (paridad) ────────────────────────

    public function test_explore_respects_workshop_visibility_scope(): void
    {
        $this->markTestSkipped('Publicaciones diferidas a V2 (defer-publications-to-v2).');
        $workshop = Workshop::factory()->create();
        $owner = $this->member($workshop);
        $this->activeService($owner, 'workshop', 'Solo mi taller');

        $sameWorkshopViewer = $this->member($workshop);
        $titles = collect($this->actingAs($sameWorkshopViewer, 'sanctum')
            ->getJson('/api/explore/services')->assertOk()->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Solo mi taller'));

        $unrelatedViewer = $this->member(Workshop::factory()->create());
        $titles = collect($this->actingAs($unrelatedViewer, 'sanctum')
            ->getJson('/api/explore/services')->assertOk()->json('data'))->pluck('title');
        $this->assertFalse($titles->contains('Solo mi taller'));
    }

    // ── 2.3 Resultados anónimos vía endpoints respaldados por la política ─────

    public function test_explore_masks_anonymous_author(): void
    {
        $this->markTestSkipped('Publicaciones diferidas a V2 (defer-publications-to-v2).');
        $owner = $this->member(Workshop::factory()->create());
        $owner->update(['name' => 'Juan', 'last_name' => 'Perez', 'profession' => 'Abogado']);
        $this->activeService($owner, 'anonymous', 'Oferta reservada');

        $viewer = $this->member(Workshop::factory()->create());
        $row = collect($this->actingAs($viewer, 'sanctum')
            ->getJson('/api/explore/services')->assertOk()->json('data'))
            ->firstWhere('title', 'Oferta reservada');

        $this->assertNotNull($row);
        $this->assertNull($row['user']['id']);
        $this->assertSame(VisibilityPolicy::MASKED_NAME, $row['user']['name']);
        $this->assertNull($row['user']['last_name']);
        $this->assertSame('Abogado', $row['user']['profession']);
        $this->assertTrue($row['user']['anonymous']);
    }

    public function test_contact_request_hides_anonymous_requestee_until_accepted(): void
    {
        $requestee = $this->member(Workshop::factory()->create());
        $requestee->update(['name' => 'Juan', 'last_name' => 'Perez']);
        $this->setBlock($requestee, 'identity', 'private', anonymousSearch: true);
        $requester = $this->member(Workshop::factory()->create());

        // Al crear, el solicitante no ve la identidad del destinatario anónimo.
        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, vi tu servicio.',
            'reason_type'  => 'offer_publication',
        ])->assertCreated()->json();
        $this->assertSame(VisibilityPolicy::MASKED_NAME, $created['requestee']['name']);
        $this->assertNull($created['requestee']['last_name']);

        // En la bandeja de enviadas tampoco.
        $sent = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/contact-requests?direction=sent')->assertOk()->json('data');
        $this->assertSame(VisibilityPolicy::MASKED_NAME, $sent[0]['requestee']['name']);

        // Al aceptar (consentimiento), la identidad se revela.
        $this->actingAs($requestee, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/accept")->assertOk()
            ->assertJsonPath('requestee.name', 'Juan');

        $sent = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/contact-requests?direction=sent')->assertOk()->json('data');
        $this->assertSame('Juan', $sent[0]['requestee']['name']);
    }

    public function test_reject_notification_does_not_reveal_anonymous_identity(): void
    {
        $requestee = $this->member(Workshop::factory()->create());
        $requestee->update(['name' => 'Juan', 'last_name' => 'Perez']);
        $this->setBlock($requestee, 'identity', 'private', anonymousSearch: true);
        $requester = $this->member(Workshop::factory()->create());

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, necesito contactarte.',
            'reason_type'  => 'other',
        ])->assertCreated()->json();

        $this->actingAs($requestee, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/reject")->assertOk();

        $notification = PontisNotification::where('user_id', $requester->id)
            ->where('type', 'contact_rejected')->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString(VisibilityPolicy::MASKED_NAME, $notification->body);
        $this->assertStringNotContainsString('Juan', $notification->body);
    }

    public function test_reject_notification_shows_name_when_identity_visible(): void
    {
        $workshop = Workshop::factory()->create();
        $requestee = $this->member($workshop);
        $requestee->update(['name' => 'Juan']);
        $requester = $this->member($workshop); // comparte taller → identidad visible (default workshop)

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, necesito contactarte.',
            'reason_type'  => 'other',
        ])->assertCreated()->json();

        $this->actingAs($requestee, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/reject")->assertOk();

        $notification = PontisNotification::where('user_id', $requester->id)
            ->where('type', 'contact_rejected')->first();
        $this->assertStringContainsString('Juan', $notification->body);
    }

    // ── Vista previa vía endpoint ─────────────────────────────────────────────

    public function test_publication_preview_endpoint(): void
    {
        $this->markTestSkipped('Publicaciones diferidas a V2 (defer-publications-to-v2).');
        $user = $this->member(Workshop::factory()->create());
        $user->update(['name' => 'Juan', 'last_name' => 'Perez']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile/publication-preview?visibility=anonymous')->assertOk()
            ->assertJsonPath('anonymous', true)
            ->assertJsonPath('author.name', VisibilityPolicy::MASKED_NAME);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile/publication-preview?visibility=registered')->assertOk()
            ->assertJsonPath('anonymous', false)
            ->assertJsonPath('author.name', 'Juan');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile/publication-preview?visibility=invalida')->assertStatus(422);
    }
}
