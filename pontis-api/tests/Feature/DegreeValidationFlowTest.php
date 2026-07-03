<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DegreeValidationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, string $role = 'member'): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshopMemberships()->attach($workshop->id, ['role' => $role, 'status' => 'active', 'is_principal' => true]);
        return $user;
    }

    public function test_self_created_degree_starts_declared(): void
    {
        $workshop = Workshop::factory()->create();
        $user = $this->member($workshop);

        $this->actingAs($user, 'sanctum')->postJson('/api/profile/degrees', [
            'degree' => 'aprendiz',
            'workshop_id' => $workshop->id,
            'start_date' => '2025-01-01',
        ])->assertCreated()->assertJsonPath('validation_status', 'declared');
    }

    public function test_admin_created_degree_starts_validated(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $user = $this->member($workshop);

        $this->actingAs($admin, 'sanctum')->postJson("/api/admin/users/{$user->id}/degrees", [
            'degree' => 'aprendiz',
            'workshop_id' => $workshop->id,
            'start_date' => '2025-01-01',
        ])->assertCreated()
            ->assertJsonPath('validation_status', 'validated')
            ->assertJsonPath('validator_id', $admin->id);
    }

    public function test_owner_cannot_edit_or_delete_validated_degree(): void
    {
        $workshop = Workshop::factory()->create();
        $user = $this->member($workshop);
        $degree = UserDegree::create([
            'user_id' => $user->id,
            'workshop_id' => $workshop->id,
            'degree' => 'aprendiz',
            'start_date' => '2025-01-01',
            'validation_status' => 'validated',
            'validated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/profile/degrees/{$degree->id}", ['notes' => 'Cambio'])
            ->assertStatus(422);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/profile/degrees/{$degree->id}")
            ->assertStatus(422);
    }

    public function test_workshop_admin_can_validate_only_own_workshop_declaration(): void
    {
        $workshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $user = $this->member($workshop);
        $otherUser = $this->member($otherWorkshop);
        $degree = UserDegree::create(['user_id' => $user->id, 'workshop_id' => $workshop->id, 'degree' => 'aprendiz', 'start_date' => '2025-01-01', 'validation_status' => 'declared']);
        $otherDegree = UserDegree::create(['user_id' => $otherUser->id, 'workshop_id' => $otherWorkshop->id, 'degree' => 'aprendiz', 'start_date' => '2025-01-01', 'validation_status' => 'declared']);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/degrees/{$otherDegree->id}/validate")
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/degrees/{$degree->id}/validate")
            ->assertOk()
            ->assertJsonPath('validation_status', 'validated')
            ->assertJsonPath('validator_id', $admin->id);
    }

    public function test_self_created_position_starts_declared_and_can_be_validated(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $user = $this->member($workshop);
        $position = Position::create(['name' => 'Secretario']);

        $created = $this->actingAs($user, 'sanctum')->postJson('/api/profile/positions', [
            'position_id' => $position->id,
            'workshop_id' => $workshop->id,
            'start_date' => '2025-01-01',
        ])->assertCreated()->assertJsonPath('validation_status', 'declared')->json();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/positions/{$created['id']}/validate")
            ->assertOk()
            ->assertJsonPath('validation_status', 'validated');
    }
}
