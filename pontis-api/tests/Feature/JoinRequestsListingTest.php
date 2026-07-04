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
