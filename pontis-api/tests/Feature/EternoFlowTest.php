<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\Service;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EternoFlowTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'status' => 'active'], $overrides));
    }

    public function test_only_superadmin_can_mark_and_revert_o_eterno(): void
    {
        $superadmin = $this->user(['role' => 'superadmin']);
        $admin = $this->user();
        $target = $this->user();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/users/{$target->id}/o-eterno")
            ->assertForbidden();

        $this->actingAs($superadmin, 'sanctum')
            ->postJson("/api/users/{$target->id}/o-eterno")
            ->assertOk()
            ->assertJsonPath('data.status', 'o_eterno');

        $this->assertSame('deceased', $target->fresh()->masonic_status);

        $this->actingAs($superadmin, 'sanctum')
            ->postJson("/api/users/{$target->id}/o-eterno/revert")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_o_eterno_denies_login_and_contact_targeting(): void
    {
        $superadmin = $this->user(['role' => 'superadmin']);
        $target = $this->user(['email' => 'eterno@example.com', 'password' => 'secret1234']);
        $requester = $this->user();

        $this->actingAs($superadmin, 'sanctum')
            ->postJson("/api/users/{$target->id}/o-eterno")
            ->assertOk();

        $this->postJson('/api/login', [
            'email' => 'eterno@example.com',
            'password' => 'secret1234',
        ])->assertStatus(422);

        $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $target->id,
            'message' => 'Necesito contactarlo por una consulta.',
            'reason_type' => 'other',
        ])->assertStatus(422);
    }

    public function test_marking_o_eterno_closes_pending_contacts_and_suspends_active_publications(): void
    {
        $superadmin = $this->user(['role' => 'superadmin']);
        $target = $this->user();
        $requester = $this->user();
        $service = Service::create([
            'user_id' => $target->id,
            'title' => 'Servicio',
            'description' => 'Detalle',
            'modality' => 'both',
            'visibility' => 'registered',
            'status' => 'active',
            'published_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);
        $contact = ContactRequest::create([
            'requester_id' => $requester->id,
            'requestee_id' => $target->id,
            'message' => 'Mensaje privado',
            'reason_type' => 'other',
            'shared_fields' => ['identity'],
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $this->actingAs($superadmin, 'sanctum')
            ->postJson("/api/users/{$target->id}/o-eterno")
            ->assertOk();

        $this->assertSame('closed', $contact->fresh()->status);
        $this->assertSame('suspended', $service->fresh()->status);
    }

    public function test_o_eterno_is_excluded_from_search_and_explore_but_history_remains(): void
    {
        // Cubre exclusión en búsqueda y en explore; explore quedó diferido a V2.
        // Se preserva para reactivar junto con publicaciones (defer-publications-to-v2).
        $this->markTestSkipped('Publicaciones diferidas a V2 (defer-publications-to-v2).');
        $superadmin = $this->user(['role' => 'superadmin']);
        $viewer = $this->user();
        $target = $this->user(['name' => 'Historico']);
        $workshop = Workshop::factory()->create();
        $target->workshopMemberships()->attach($workshop->id, ['role' => 'member', 'status' => 'active', 'is_principal' => true]);
        $degree = UserDegree::create([
            'user_id' => $target->id,
            'workshop_id' => $workshop->id,
            'degree' => 'aprendiz',
            'start_date' => '2020-01-01',
            'validation_status' => 'validated',
            'validated_at' => now(),
        ]);
        Service::create([
            'user_id' => $target->id,
            'title' => 'No visible',
            'description' => 'Detalle',
            'modality' => 'both',
            'visibility' => 'registered',
            'status' => 'active',
            'published_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $this->actingAs($superadmin, 'sanctum')
            ->postJson("/api/users/{$target->id}/o-eterno")
            ->assertOk();

        $people = collect($this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?q=Historico')
            ->assertOk()
            ->json('data'));
        $this->assertFalse($people->pluck('id')->contains($target->id));

        $services = collect($this->actingAs($viewer, 'sanctum')
            ->getJson('/api/explore/services')
            ->assertOk()
            ->json('data'));
        $this->assertFalse($services->pluck('title')->contains('No visible'));

        $this->assertDatabaseHas('user_degrees', ['id' => $degree->id, 'user_id' => $target->id]);
    }
}
