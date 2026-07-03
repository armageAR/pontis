<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\UserPosition;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPendingValidationCountTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, string $role = 'member', array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'user', 'status' => 'active'], $attrs));
        $user->workshopMemberships()->attach($workshop->id, ['role' => $role, 'status' => 'active', 'is_principal' => true]);
        return $user;
    }

    private function declaredDegree(User $owner, ?Workshop $workshop): UserDegree
    {
        return UserDegree::create([
            'user_id' => $owner->id,
            'workshop_id' => $workshop?->id,
            'degree' => 'aprendiz',
            'start_date' => '2025-01-01',
            'validation_status' => 'declared',
        ]);
    }

    private function declaredPosition(User $owner, Workshop $workshop): UserPosition
    {
        $position = Position::create(['name' => 'Secretario']);
        return UserPosition::create([
            'user_id' => $owner->id,
            'position_id' => $position->id,
            'workshop_id' => $workshop->id,
            'start_date' => '2025-01-01',
            'validation_status' => 'declared',
        ]);
    }

    public function test_workshop_admin_count_is_scoped_to_administered_talleres(): void
    {
        $adminWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $admin = $this->member($adminWorkshop, 'admin');
        $memberHere = $this->member($adminWorkshop);
        $memberThere = $this->member($otherWorkshop);

        // 2 pendientes en el Taller administrado (1 grado + 1 cargo).
        $this->declaredDegree($memberHere, $adminWorkshop);
        $this->declaredPosition($memberHere, $adminWorkshop);
        // 1 pendiente en otro Taller: no debe contar.
        $this->declaredDegree($memberThere, $otherWorkshop);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('is_workshop_admin', true)
            ->assertJsonPath('pending_validation_count', 2);
    }

    public function test_superadmin_count_is_global_and_includes_null_workshop_degrees(): void
    {
        $workshop = Workshop::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
        $member = $this->member($workshop);

        $this->declaredDegree($member, $workshop);
        $this->declaredDegree($member, null); // grado sin taller
        $this->declaredPosition($member, $workshop);

        $this->actingAs($superadmin, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('pending_validation_count', 3);
    }

    public function test_regular_member_is_not_workshop_admin_and_has_zero_count(): void
    {
        $workshop = Workshop::factory()->create();
        $user = $this->member($workshop);
        $this->declaredDegree($user, $workshop);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('is_workshop_admin', false)
            ->assertJsonPath('pending_validation_count', 0);
    }
}
