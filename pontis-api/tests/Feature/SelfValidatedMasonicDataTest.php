<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\PontisNotification;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\UserPosition;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfValidatedMasonicDataTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, string $role = 'member', array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'user', 'status' => 'active'], $attrs));
        $user->workshopMemberships()->attach($workshop->id, ['role' => $role, 'status' => 'active', 'is_principal' => true]);
        return $user;
    }

    // ── 2.1 Admin de Taller self-created records in administered Taller ────────

    public function test_workshop_admin_self_created_degree_is_auto_validated(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/profile/degrees', [
                'degree' => 'aprendiz',
                'workshop_id' => $workshop->id,
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'validated')
            ->assertJsonPath('validator_id', $admin->id)
            ->assertJson(fn ($json) => $json->where('validated_at', fn ($v) => ! empty($v))->etc());

        // No queda pendiente ni genera notificación de validación pendiente.
        $this->assertDatabaseMissing('user_degrees', ['user_id' => $admin->id, 'validation_status' => 'declared']);
        $this->assertDatabaseMissing('pontis_notifications', ['type' => 'degree_validation_pending']);
    }

    public function test_workshop_admin_self_created_position_is_auto_validated(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $position = Position::create(['name' => 'Secretario']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/profile/positions', [
                'position_id' => $position->id,
                'workshop_id' => $workshop->id,
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'validated')
            ->assertJsonPath('validator_id', $admin->id)
            ->assertJson(fn ($json) => $json->where('validated_at', fn ($v) => ! empty($v))->etc());

        $this->assertDatabaseMissing('user_positions', ['user_id' => $admin->id, 'validation_status' => 'declared']);
        $this->assertDatabaseMissing('pontis_notifications', ['type' => 'position_validation_pending']);
    }

    // ── 2.2 Admin de Taller records for non-administered Taller still pending ──

    public function test_workshop_admin_self_created_degree_for_non_administered_taller_stays_declared(): void
    {
        $adminWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        // Admin en un Taller, miembro regular en otro.
        $admin = $this->member($adminWorkshop, 'admin');
        $admin->workshopMemberships()->attach($otherWorkshop->id, ['role' => 'member', 'status' => 'active', 'is_principal' => false]);
        // El otro Taller tiene su propio admin, que es quien debe validar.
        $otherAdmin = $this->member($otherWorkshop, 'admin');

        $created = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/profile/degrees', [
                'degree' => 'aprendiz',
                'workshop_id' => $otherWorkshop->id,
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'declared')
            ->json();

        // Notifica al validador legítimo del Taller declarado y queda pendiente.
        $this->assertDatabaseHas('pontis_notifications', ['user_id' => $otherAdmin->id, 'type' => 'degree_validation_pending']);
        $this->actingAs($otherAdmin, 'sanctum')
            ->getJson('/api/admin/degree-validations')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);
    }

    public function test_workshop_admin_self_created_position_for_non_administered_taller_stays_declared(): void
    {
        $adminWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $admin = $this->member($adminWorkshop, 'admin');
        $admin->workshopMemberships()->attach($otherWorkshop->id, ['role' => 'member', 'status' => 'active', 'is_principal' => false]);
        $otherAdmin = $this->member($otherWorkshop, 'admin');
        $position = Position::create(['name' => 'Secretario']);

        $created = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/profile/positions', [
                'position_id' => $position->id,
                'workshop_id' => $otherWorkshop->id,
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'declared')
            ->json();

        $this->assertDatabaseHas('pontis_notifications', ['user_id' => $otherAdmin->id, 'type' => 'position_validation_pending']);
        $this->actingAs($otherAdmin, 'sanctum')
            ->getJson('/api/admin/position-validations')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);
    }

    // ── 2.3 Superadmin self-created records ───────────────────────────────────

    public function test_superadmin_self_created_degree_with_workshop_is_auto_validated(): void
    {
        $workshop = Workshop::factory()->create();
        $superadmin = $this->member($workshop, 'member', ['role' => 'superadmin']);

        $this->actingAs($superadmin, 'sanctum')
            ->postJson('/api/profile/degrees', [
                'degree' => 'aprendiz',
                'workshop_id' => $workshop->id,
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'validated')
            ->assertJsonPath('validator_id', $superadmin->id);
    }

    public function test_superadmin_self_created_degree_without_workshop_is_auto_validated(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);

        $this->actingAs($superadmin, 'sanctum')
            ->postJson('/api/profile/degrees', [
                'degree' => 'aprendiz',
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'validated')
            ->assertJsonPath('validator_id', $superadmin->id);

        $this->assertDatabaseMissing('pontis_notifications', ['type' => 'degree_validation_pending']);
    }

    public function test_superadmin_self_created_position_is_auto_validated(): void
    {
        $workshop = Workshop::factory()->create();
        $superadmin = $this->member($workshop, 'member', ['role' => 'superadmin']);
        $position = Position::create(['name' => 'Secretario']);

        $this->actingAs($superadmin, 'sanctum')
            ->postJson('/api/profile/positions', [
                'position_id' => $position->id,
                'workshop_id' => $workshop->id,
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'validated')
            ->assertJsonPath('validator_id', $superadmin->id);
    }

    // ── Regular Hermano remains pending (regression guard) ────────────────────

    public function test_regular_member_self_created_degree_stays_declared(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $user = $this->member($workshop);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/degrees', [
                'degree' => 'aprendiz',
                'workshop_id' => $workshop->id,
                'start_date' => '2025-01-01',
            ])
            ->assertCreated()
            ->assertJsonPath('validation_status', 'declared');

        $this->assertDatabaseHas('pontis_notifications', ['user_id' => $admin->id, 'type' => 'degree_validation_pending']);
    }
}
