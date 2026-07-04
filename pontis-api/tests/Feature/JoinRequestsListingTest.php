<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoinRequestsListingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(Workshop $workshop): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshopMemberships()->attach($workshop->id, ['role' => 'admin', 'status' => 'active', 'is_principal' => true]);
        return $user;
    }

    private function joinRequest(Workshop $workshop, string $status = 'pending'): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'pending']);
        $user->workshopMemberships()->attach($workshop->id, [
            'role' => 'member', 'status' => $status, 'requested_by_user' => true, 'is_principal' => true,
        ]);
        return $user;
    }

    public function test_admin_de_taller_sees_only_scoped_join_requests(): void
    {
        $workshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $admin = $this->admin($workshop);
        $scoped = $this->joinRequest($workshop);
        $unrelated = $this->joinRequest($otherWorkshop);

        $ids = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/join-requests')
            ->assertOk()
            ->json())->pluck('user_id');

        $this->assertContains($scoped->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_active_hermano_requesting_second_taller_is_listed(): void
    {
        // Un Hermano ya validado (status active) que pide ingreso a un segundo
        // Taller debe ser visible para el admin de ese Taller.
        $principal = Workshop::factory()->create();
        $secondWorkshop = Workshop::factory()->create();
        $admin = $this->admin($secondWorkshop);

        $hermano = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $hermano->workshopMemberships()->attach($principal->id, ['role' => 'member', 'status' => 'active', 'is_principal' => true]);
        $hermano->workshopMemberships()->attach($secondWorkshop->id, ['role' => 'member', 'status' => 'pending', 'requested_by_user' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/join-requests')
            ->assertOk()
            ->assertJsonFragment(['user_id' => $hermano->id, 'workshop_id' => $secondWorkshop->id]);
    }

    public function test_join_request_from_unverified_user_is_not_listed(): void
    {
        // Usuario que aún no verificó su email (status verifying): no debe
        // aparecer hasta que verifique.
        $workshop = Workshop::factory()->create();
        $admin = $this->admin($workshop);
        $unverified = User::factory()->create(['role' => 'user', 'status' => 'verifying']);
        $unverified->workshopMemberships()->attach($workshop->id, ['role' => 'member', 'status' => 'pending', 'requested_by_user' => true, 'is_principal' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/join-requests')
            ->assertOk()
            ->assertJsonMissing(['user_id' => $unverified->id]);
    }

    public function test_correction_requested_join_requests_are_included(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->admin($workshop);
        $correction = $this->joinRequest($workshop, 'correction_requested');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/join-requests')
            ->assertOk()
            ->assertJsonFragment(['user_id' => $correction->id, 'membership_status' => 'correction_requested']);
    }

    public function test_superadmin_sees_all_join_requests(): void
    {
        $workshopA = Workshop::factory()->create();
        $workshopB = Workshop::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
        $a = $this->joinRequest($workshopA);
        $b = $this->joinRequest($workshopB);

        $ids = collect($this->actingAs($superadmin, 'sanctum')
            ->getJson('/api/admin/join-requests')
            ->assertOk()
            ->json())->pluck('user_id');

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    public function test_regular_member_sees_no_join_requests(): void
    {
        $workshop = Workshop::factory()->create();
        $member = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $member->workshopMemberships()->attach($workshop->id, ['role' => 'member', 'status' => 'active', 'is_principal' => true]);
        $this->joinRequest($workshop);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/admin/join-requests')
            ->assertOk()
            ->assertJsonCount(0);
    }
}
