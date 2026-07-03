<?php

namespace Tests\Feature;

use App\Models\Need;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    // ── Publicar sin autorización previa ──────────────────────────────────────

    public function test_registered_user_creates_active_service_without_authorization(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/services', [
                'title'         => 'Servicio de prueba',
                'description'   => 'Descripción de prueba',
                'visibility'    => 'registered',
                'modality'      => 'both',
                'publish'       => true,
                'validity_days' => 30,
                'preview_confirmed' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'active');

        $this->assertDatabaseHas('services', [
            'user_id' => $user->id,
            'title'   => 'Servicio de prueba',
            'status'  => 'active',
        ]);
        $this->assertDatabaseMissing('services', [
            'user_id' => $user->id,
            'status'  => 'pending_authorization',
        ]);
    }

    public function test_registered_user_creates_active_need_without_authorization(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/needs', [
                'title'         => 'Necesidad de prueba',
                'description'   => 'Descripción de prueba',
                'visibility'    => 'registered',
                'publish'       => true,
                'validity_days' => 30,
                'preview_confirmed' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'active');

        $this->assertDatabaseHas('needs', [
            'user_id' => $user->id,
            'status'  => 'active',
        ]);
        $this->assertDatabaseMissing('needs', [
            'user_id' => $user->id,
            'status'  => 'pending_authorization',
        ]);
    }

    public function test_new_service_appears_in_owner_listing_immediately(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/services', [
                'title'         => 'Servicio visible',
                'description'   => 'Descripción',
                'publish'       => true,
                'validity_days' => 30,
                'preview_confirmed' => true,
            ])
            ->assertCreated();

        $listing = $this->actingAs($user, 'sanctum')
            ->getJson('/api/services')
            ->assertOk()
            ->json('data');

        $this->assertTrue(collect($listing)->pluck('title')->contains('Servicio visible'));
    }

    // ── Estado "pedir corrección" retirado del flujo activo ───────────────────

    public function test_service_correction_endpoint_is_removed(): void
    {
        $user = $this->user();
        $service = Service::create([
            'user_id'    => $user->id,
            'title'      => 'X',
            'modality'   => 'both',
            'visibility' => 'private',
            'status'     => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/services/{$service->id}/request-correction", ['notes' => 'corregir'])
            ->assertNotFound();
    }

    public function test_need_correction_endpoint_is_removed(): void
    {
        $user = $this->user();
        $need = Need::create([
            'user_id'    => $user->id,
            'title'      => 'X',
            'visibility' => 'private',
            'status'     => 'open',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/needs/{$need->id}/request-correction", ['notes' => 'corregir'])
            ->assertNotFound();
    }
}
