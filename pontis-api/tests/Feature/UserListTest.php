<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    private function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function workshopAdmin(Workshop $workshop): User
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->workshops()->attach($workshop->id, ['role' => 'admin']);
        return $user;
    }

    private function workshopMember(Workshop $workshop): User
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->workshops()->attach($workshop->id, ['role' => 'member']);
        return $user;
    }

    // ── access ───────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_users(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_any_authenticated_user_can_list_users(): void
    {
        $this->actingAs($this->user(), 'sanctum')
             ->getJson('/api/users')
             ->assertOk();
    }

    // ── scope ────────────────────────────────────────────────────────────────

    public function test_superadmin_sees_all_users(): void
    {
        $sa = $this->superAdmin();
        User::factory()->count(3)->create();

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users')
             ->assertOk()
             ->assertJsonPath('meta.total', 4);
    }

    public function test_workshop_admin_sees_only_own_workshop_users(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $member = $this->workshopMember($workshop);

        $otherWorkshop = Workshop::factory()->create();
        $outsider = $this->workshopMember($otherWorkshop);

        $response = $this->actingAs($admin, 'sanctum')
             ->getJson('/api/users')
             ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($admin->id, $ids);
        $this->assertContains($member->id, $ids);
        $this->assertNotContains($outsider->id, $ids);
    }

    public function test_regular_user_sees_only_own_workshop_users(): void
    {
        $workshop = Workshop::factory()->create();
        $user = $this->workshopMember($workshop);
        $fellow = $this->workshopMember($workshop);

        $otherWorkshop = Workshop::factory()->create();
        $stranger = $this->workshopMember($otherWorkshop);

        $response = $this->actingAs($user, 'sanctum')
             ->getJson('/api/users')
             ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($user->id, $ids);
        $this->assertContains($fellow->id, $ids);
        $this->assertNotContains($stranger->id, $ids);
    }

    // ── filters ──────────────────────────────────────────────────────────────

    public function test_filter_by_search(): void
    {
        $sa = $this->superAdmin();
        User::factory()->create(['name' => 'Juan Perez']);
        User::factory()->create(['name' => 'Maria Lopez']);

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?search=juan')
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.name', 'Juan Perez');
    }

    public function test_filter_by_global_role_superadmin(): void
    {
        $sa = $this->superAdmin();
        User::factory()->create(['role' => 'user']);

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?role=superadmin')
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.role', 'superadmin');
    }

    public function test_filter_by_workshop_role(): void
    {
        $sa = $this->superAdmin();
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $member = $this->workshopMember($workshop);

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?workshop_role=admin')
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.id', $admin->id);

        unset($member);
    }

    public function test_filter_by_status(): void
    {
        $sa = $this->superAdmin();
        User::factory()->create(['status' => 'pending']);
        User::factory()->create(['status' => 'active']);

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?status=pending')
             ->assertOk()
             ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_workshop(): void
    {
        $sa = $this->superAdmin();
        $w1 = Workshop::factory()->create();
        $w2 = Workshop::factory()->create();
        $u1 = $this->workshopMember($w1);
        $u2 = $this->workshopMember($w2);

        $this->actingAs($sa, 'sanctum')
             ->getJson("/api/users?workshop_id={$w1->id}")
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.id', $u1->id);

        unset($u2);
    }

    // ── pagination & sort ────────────────────────────────────────────────────

    public function test_pagination(): void
    {
        $sa = $this->superAdmin();
        User::factory()->count(5)->create();

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?per_page=2')
             ->assertOk()
             ->assertJsonCount(2, 'data')
             ->assertJsonPath('meta.total', 6);
    }

    public function test_sort_by_name(): void
    {
        $sa = $this->superAdmin();
        User::factory()->create(['name' => 'Carlos']);
        User::factory()->create(['name' => 'Ana']);

        $response = $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?sort_by=name&sort_direction=asc')
             ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals($names, collect($names)->sort()->values()->toArray());
    }

    // ── response structure ───────────────────────────────────────────────────

    public function test_response_includes_workshop_with_pivot_role(): void
    {
        $sa = $this->superAdmin();
        $workshop = Workshop::factory()->create(['name' => 'UNION DEL PLATA', 'number' => 1]);
        $user = $this->workshopAdmin($workshop);

        $response = $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users')
             ->assertOk();

        $userData = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertNotEmpty($userData['workshops']);
        $this->assertEquals('UNION DEL PLATA', $userData['workshops'][0]['name']);
        $this->assertEquals('admin', $userData['workshops'][0]['workshop_role']);
    }

    // ── update status ────────────────────────────────────────────────────────

    public function test_superadmin_can_change_any_user_status(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->pending()->create();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}/status", ['status' => 'active'])
             ->assertOk()
             ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
    }

    public function test_superadmin_cannot_change_own_status(): void
    {
        $sa = $this->superAdmin();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$sa->id}/status", ['status' => 'suspended'])
             ->assertForbidden();
    }

    public function test_workshop_admin_can_change_status_of_own_member(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $member = User::factory()->pending()->create();
        $member->workshops()->attach($workshop->id, ['role' => 'member']);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$member->id}/status", ['status' => 'active'])
             ->assertOk()
             ->assertJsonPath('data.status', 'active');
    }

    public function test_workshop_admin_cannot_change_status_of_other_workshop_member(): void
    {
        $workshop1 = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop1);

        $workshop2 = Workshop::factory()->create();
        $outsider = $this->workshopMember($workshop2);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$outsider->id}/status", ['status' => 'suspended'])
             ->assertForbidden();
    }

    public function test_workshop_admin_cannot_change_own_status(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$admin->id}/status", ['status' => 'inactive'])
             ->assertForbidden();
    }

    public function test_regular_member_cannot_change_status(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);
        $target = User::factory()->create();

        $this->actingAs($member, 'sanctum')
             ->patchJson("/api/users/{$target->id}/status", ['status' => 'active'])
             ->assertForbidden();
    }

    public function test_can_activate_user_without_verified_email(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->pending()->unverified()->create();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}/status", ['status' => 'active'])
             ->assertOk()
             ->assertJsonPath('data.status', 'active');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_superadmin_can_set_all_statuses(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->create();

        foreach (['pending', 'active', 'rejected', 'suspended', 'inactive'] as $status) {
            $this->actingAs($sa, 'sanctum')
                 ->patchJson("/api/users/{$user->id}/status", ['status' => $status])
                 ->assertOk()
                 ->assertJsonPath('data.status', $status);
        }
    }

    // ── update user ──────────────────────────────────────────────────────────

    public function test_superadmin_can_edit_any_user(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->create(['name' => 'Original']);

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}", ['name' => 'Updated', 'role' => 'superadmin'])
             ->assertOk()
             ->assertJsonPath('data.name', 'Updated')
             ->assertJsonPath('data.role', 'superadmin');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated', 'role' => 'superadmin']);
    }

    public function test_superadmin_can_edit_themselves(): void
    {
        $sa = $this->superAdmin();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$sa->id}", ['name' => 'New SA Name'])
             ->assertOk()
             ->assertJsonPath('data.name', 'New SA Name');
    }

    public function test_non_superadmin_cannot_edit_themselves(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$admin->id}", ['name' => 'Hack'])
             ->assertForbidden();
    }

    public function test_workshop_admin_can_edit_own_member(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $member = $this->workshopMember($workshop);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$member->id}", ['name' => 'Updated'])
             ->assertOk()
             ->assertJsonPath('data.name', 'Updated');
    }

    public function test_workshop_admin_cannot_edit_other_workshop_member(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);

        $outsider = $this->workshopMember(Workshop::factory()->create());

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$outsider->id}", ['name' => 'Hack'])
             ->assertForbidden();
    }

    public function test_workshop_admin_cannot_change_global_role(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $member = $this->workshopMember($workshop);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$member->id}", ['role' => 'superadmin'])
             ->assertForbidden();
    }

    public function test_regular_member_cannot_edit_others(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);
        $target = User::factory()->create();

        $this->actingAs($member, 'sanctum')
             ->patchJson("/api/users/{$target->id}", ['name' => 'Hack'])
             ->assertForbidden();
    }

    public function test_edit_email_unique_ignores_same_user(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->create(['email' => 'original@test.com']);

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}", ['email' => 'original@test.com'])
             ->assertOk();
    }

    public function test_edit_email_rejects_duplicate(): void
    {
        $sa = $this->superAdmin();
        User::factory()->create(['email' => 'taken@test.com']);
        $user = User::factory()->create();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}", ['email' => 'taken@test.com'])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['email']);
    }

    // ── update password ──────────────────────────────────────────────────────

    public function test_superadmin_can_change_any_password(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->create();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}/password", [
                 'password' => 'newpassword123',
                 'password_confirmation' => 'newpassword123',
             ])
             ->assertOk();

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_workshop_admin_can_change_member_password(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $member = $this->workshopMember($workshop);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$member->id}/password", [
                 'password' => 'newpassword123',
                 'password_confirmation' => 'newpassword123',
             ])
             ->assertOk();
    }

    public function test_cannot_change_own_password_via_admin_endpoint(): void
    {
        $sa = $this->superAdmin();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$sa->id}/password", [
                 'password' => 'newpassword123',
                 'password_confirmation' => 'newpassword123',
             ])
             ->assertForbidden();
    }

    public function test_password_confirmation_must_match(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->create();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}/password", [
                 'password' => 'newpassword123',
                 'password_confirmation' => 'different',
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['password']);
    }

    public function test_regular_member_cannot_change_others_password(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);
        $target = User::factory()->create();

        $this->actingAs($member, 'sanctum')
             ->patchJson("/api/users/{$target->id}/password", [
                 'password' => 'newpassword123',
                 'password_confirmation' => 'newpassword123',
             ])
             ->assertForbidden();
    }
}
