<?php

namespace Tests\Feature;

use App\Models\Need;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function activeService(User $user, array $overrides = []): Service
    {
        return Service::create(array_merge([
            'user_id'      => $user->id,
            'title'        => 'Servicio activo',
            'description'  => 'Desc',
            'modality'     => 'both',
            'visibility'   => 'registered',
            'status'       => 'active',
            'published_at' => Carbon::now()->subDays(2),
            'expires_at'   => Carbon::now()->addDays(20),
        ], $overrides));
    }

    private function activeNeed(User $user, array $overrides = []): Need
    {
        return Need::create(array_merge([
            'user_id'      => $user->id,
            'title'        => 'Necesidad activa',
            'description'  => 'Desc',
            'visibility'   => 'registered',
            'status'       => 'active',
            'published_at' => Carbon::now()->subDays(2),
            'expires_at'   => Carbon::now()->addDays(20),
        ], $overrides));
    }

    // ── Publish / draft ───────────────────────────────────────────────────────

    public function test_publish_service_sets_active_with_dates(): void
    {
        $user = $this->user();

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/services', [
            'title' => 'Oferta', 'description' => 'Detalle', 'visibility' => 'registered',
            'publish' => true, 'validity_days' => 30, 'preview_confirmed' => true,
        ])->assertCreated()->assertJsonPath('status', 'active');

        $service = Service::find($res->json('id'));
        $this->assertNotNull($service->published_at);
        $this->assertNotNull($service->expires_at);
        $this->assertTrue($service->expires_at->isFuture());
        // Expiration must not exceed 90 days after publication.
        $this->assertTrue($service->expires_at->lessThanOrEqualTo($service->published_at->copy()->addDays(90)));
    }

    public function test_save_service_as_draft_has_no_dates_and_is_hidden_from_explore(): void
    {
        $owner = $this->user();
        $viewer = $this->user();

        $this->actingAs($owner, 'sanctum')->postJson('/api/services', [
            'title' => 'Borrador', 'description' => 'Detalle', 'visibility' => 'registered',
            'publish' => false,
        ])->assertCreated()->assertJsonPath('status', 'draft');

        $this->assertDatabaseHas('services', ['user_id' => $owner->id, 'status' => 'draft', 'published_at' => null, 'expires_at' => null]);

        $titles = collect($this->actingAs($viewer, 'sanctum')->getJson('/api/explore/services')->assertOk()->json('data'))->pluck('title');
        $this->assertFalse($titles->contains('Borrador'));
    }

    public function test_publish_need_sets_active_with_dates(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')->postJson('/api/needs', [
            'title' => 'Necesito', 'description' => 'Detalle', 'visibility' => 'registered',
            'publish' => true, 'validity_days' => 60, 'preview_confirmed' => true,
        ])->assertCreated()->assertJsonPath('status', 'active');

        $this->assertDatabaseMissing('needs', ['user_id' => $user->id, 'expires_at' => null]);
    }

    // ── Suspend ───────────────────────────────────────────────────────────────

    public function test_suspend_service_removes_it_from_explore(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $service = $this->activeService($owner, ['title' => 'Suspendible']);

        $this->actingAs($owner, 'sanctum')->postJson("/api/services/{$service->id}/suspend")
            ->assertOk()->assertJsonPath('status', 'suspended');

        $titles = collect($this->actingAs($viewer, 'sanctum')->getJson('/api/explore/services')->assertOk()->json('data'))->pluck('title');
        $this->assertFalse($titles->contains('Suspendible'));
    }

    public function test_suspend_need_sets_suspended(): void
    {
        $owner = $this->user();
        $need = $this->activeNeed($owner);

        $this->actingAs($owner, 'sanctum')->postJson("/api/needs/{$need->id}/suspend")
            ->assertOk()->assertJsonPath('status', 'suspended');
    }

    public function test_user_cannot_suspend_another_users_publication(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $service = $this->activeService($owner);

        $this->actingAs($other, 'sanctum')->postJson("/api/services/{$service->id}/suspend")
            ->assertForbidden();
    }

    // ── Expiration ────────────────────────────────────────────────────────────

    public function test_expired_service_is_hidden_from_explore_but_visible_to_owner(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $expired = $this->activeService($owner, [
            'title' => 'Vencido', 'published_at' => Carbon::now()->subDays(100), 'expires_at' => Carbon::now()->subDay(),
        ]);

        // Hidden from explore.
        $titles = collect($this->actingAs($viewer, 'sanctum')->getJson('/api/explore/services')->assertOk()->json('data'))->pluck('title');
        $this->assertFalse($titles->contains('Vencido'));

        // Visible to owner with effective_status "expired".
        $mine = collect($this->actingAs($owner, 'sanctum')->getJson('/api/services')->assertOk()->json('data'))
            ->firstWhere('id', $expired->id);
        $this->assertSame('expired', $mine['effective_status']);
    }

    public function test_owner_listing_includes_all_lifecycle_states(): void
    {
        $owner = $this->user();
        $this->activeService($owner, ['title' => 'A']);
        $this->activeService($owner, ['title' => 'B', 'status' => 'draft', 'published_at' => null, 'expires_at' => null]);
        $this->activeService($owner, ['title' => 'C', 'status' => 'suspended']);
        $this->activeService($owner, ['title' => 'D', 'expires_at' => Carbon::now()->subDay()]);

        $titles = collect($this->actingAs($owner, 'sanctum')->getJson('/api/services?per_page=50')->assertOk()->json('data'))->pluck('title');
        foreach (['A', 'B', 'C', 'D'] as $t) {
            $this->assertTrue($titles->contains($t), "owner listing must include {$t}");
        }
    }

    // ── Republish (updates same record, no clone) ─────────────────────────────

    public function test_republish_expired_service_updates_same_record(): void
    {
        $owner = $this->user();
        $expired = $this->activeService($owner, [
            'title' => 'Para republicar', 'published_at' => Carbon::now()->subDays(100), 'expires_at' => Carbon::now()->subDay(),
        ]);
        $oldPublishedAt = $expired->published_at;

        $this->actingAs($owner, 'sanctum')->patchJson("/api/services/{$expired->id}", [
            'title' => 'Para republicar', 'description' => 'Actualizada', 'visibility' => 'registered',
            'publish' => true, 'validity_days' => 60, 'preview_confirmed' => true,
        ])->assertOk()->assertJsonPath('status', 'active');

        // No duplicate created.
        $this->assertSame(1, Service::where('user_id', $owner->id)->count());

        $fresh = $expired->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertTrue($fresh->expires_at->isFuture());
        $this->assertTrue($fresh->published_at->greaterThan($oldPublishedAt));
    }

    // ── Validity constraints ──────────────────────────────────────────────────

    public function test_validity_must_be_one_of_allowed_options(): void
    {
        $user = $this->user();
        $base = ['title' => 'X', 'description' => 'Y', 'visibility' => 'registered', 'publish' => true, 'preview_confirmed' => true];

        $this->actingAs($user, 'sanctum')->postJson('/api/services', $base + ['validity_days' => 45])
            ->assertStatus(422)->assertJsonValidationErrors('validity_days');

        $this->actingAs($user, 'sanctum')->postJson('/api/services', $base + ['validity_days' => 120])
            ->assertStatus(422)->assertJsonValidationErrors('validity_days');
    }

    public function test_publish_requires_validity_days(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')->postJson('/api/services', [
            'title' => 'X', 'description' => 'Y', 'visibility' => 'registered', 'publish' => true, 'preview_confirmed' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('validity_days');
    }

    public function test_publish_requires_preview_confirmation(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')->postJson('/api/services', [
            'title' => 'X', 'description' => 'Y', 'visibility' => 'registered',
            'publish' => true, 'validity_days' => 30,
        ])->assertStatus(422)->assertJsonValidationErrors('preview_confirmed');

        $this->actingAs($user, 'sanctum')->postJson('/api/needs', [
            'title' => 'X', 'description' => 'Y', 'visibility' => 'registered',
            'publish' => true, 'validity_days' => 30,
        ])->assertStatus(422)->assertJsonValidationErrors('preview_confirmed');
    }

    // ── Required fields ───────────────────────────────────────────────────────

    public function test_title_and_description_are_required(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')->postJson('/api/services', [
            'visibility' => 'registered', 'publish' => false,
        ])->assertStatus(422)->assertJsonValidationErrors(['title', 'description']);

        $this->actingAs($user, 'sanctum')->postJson('/api/needs', [
            'visibility' => 'registered', 'publish' => false,
        ])->assertStatus(422)->assertJsonValidationErrors(['title', 'description']);
    }
}
