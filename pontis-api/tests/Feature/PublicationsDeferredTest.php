<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Publicaciones diferidas a V2 (defer-publications-to-v2): ningún rol puede
 * acceder al módulo de publicaciones en V1; los endpoints responden 403 y no
 * mutan datos. El código, modelos, migraciones y datos se conservan.
 */
class PublicationsDeferredTest extends TestCase
{
    use RefreshDatabase;

    private function regularUser(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function workshopAdmin(): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshopMemberships()->attach(Workshop::factory()->create()->id, ['role' => 'admin', 'status' => 'active', 'is_principal' => true]);
        return $user;
    }

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
    }

    /** @return array<string, User> */
    private function roles(): array
    {
        return ['hermano' => $this->regularUser(), 'admin_taller' => $this->workshopAdmin(), 'superadmin' => $this->superadmin()];
    }

    public function test_all_roles_are_forbidden_from_publication_read_endpoints(): void
    {
        $endpoints = [
            '/api/services',
            '/api/needs',
            '/api/explore/services',
            '/api/explore/needs',
            '/api/service-categories',
            '/api/profile/publication-preview?visibility=registered',
        ];

        foreach ($this->roles() as $role => $user) {
            foreach ($endpoints as $endpoint) {
                $this->actingAs($user, 'sanctum')
                    ->getJson($endpoint)
                    ->assertForbidden(); // 403, "$role → $endpoint"
            }
        }
    }

    public function test_creating_a_service_is_forbidden_and_does_not_mutate(): void
    {
        foreach ($this->roles() as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/services', [
                    'title' => 'Oferta', 'description' => 'Desc', 'modality' => 'both',
                    'visibility' => 'registered', 'validity_days' => 30, 'preview_confirmed' => true,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('services', 0);
    }

    public function test_creating_a_need_is_forbidden_and_does_not_mutate(): void
    {
        foreach ($this->roles() as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/needs', [
                    'title' => 'Necesidad', 'description' => 'Desc',
                    'visibility' => 'registered', 'validity_days' => 30, 'preview_confirmed' => true,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('needs', 0);
    }

    public function test_suspend_and_delete_of_existing_publications_are_forbidden_and_do_not_mutate(): void
    {
        // Registro dormido creado directamente (sin pasar por el endpoint bloqueado):
        // representa datos preexistentes que deben quedar intactos en V1.
        $owner = $this->regularUser();
        $service = \App\Models\Service::create([
            'user_id' => $owner->id, 'title' => 'Oferta dormida', 'description' => 'Desc',
            'modality' => 'both', 'visibility' => 'registered', 'status' => 'active',
        ]);
        $need = \App\Models\Need::create([
            'user_id' => $owner->id, 'title' => 'Necesidad dormida', 'description' => 'Desc',
            'visibility' => 'registered', 'status' => 'active',
        ]);

        $this->actingAs($owner, 'sanctum')->postJson("/api/services/{$service->id}/suspend")->assertForbidden();
        $this->actingAs($owner, 'sanctum')->deleteJson("/api/services/{$service->id}")->assertForbidden();
        $this->actingAs($owner, 'sanctum')->postJson("/api/needs/{$need->id}/suspend")->assertForbidden();
        $this->actingAs($owner, 'sanctum')->deleteJson("/api/needs/{$need->id}")->assertForbidden();

        // Los registros dormidos siguen intactos.
        $this->assertDatabaseHas('services', ['id' => $service->id, 'status' => 'active']);
        $this->assertDatabaseHas('needs', ['id' => $need->id, 'status' => 'active']);
    }
}
