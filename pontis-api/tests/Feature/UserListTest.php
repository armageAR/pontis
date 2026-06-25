<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function regularUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    // ── access ───────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_users(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_any_authenticated_user_can_list_users(): void
    {
        $user = $this->regularUser();
        $workshop = Workshop::factory()->create();
        $user->workshops()->attach($workshop);

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/users')
             ->assertOk();
    }

    // ── scope by role ────────────────────────────────────────────────────────

    public function test_superadmin_sees_all_users(): void
    {
        $sa = $this->superAdmin();
        User::factory()->count(3)->create();

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users')
             ->assertOk()
             ->assertJsonPath('meta.total', 4);
    }

    public function test_admin_sees_only_users_from_own_workshops(): void
    {
        $admin = $this->admin();
        $workshop = Workshop::factory()->create();
        $admin->workshops()->attach($workshop);

        $memberInWorkshop = User::factory()->create();
        $memberInWorkshop->workshops()->attach($workshop);

        $otherWorkshop = Workshop::factory()->create();
        $outsider = User::factory()->create();
        $outsider->workshops()->attach($otherWorkshop);

        $response = $this->actingAs($admin, 'sanctum')
             ->getJson('/api/users')
             ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($admin->id, $ids);
        $this->assertContains($memberInWorkshop->id, $ids);
        $this->assertNotContains($outsider->id, $ids);
    }

    public function test_regular_user_sees_only_users_from_own_workshops(): void
    {
        $user = $this->regularUser();
        $workshop = Workshop::factory()->create();
        $user->workshops()->attach($workshop);

        $fellow = User::factory()->create();
        $fellow->workshops()->attach($workshop);

        $stranger = User::factory()->create();
        $otherWs = Workshop::factory()->create();
        $stranger->workshops()->attach($otherWs);

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

    public function test_filter_by_role(): void
    {
        $sa = $this->superAdmin();
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'user']);

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?role=admin')
             ->assertOk()
             ->assertJsonCount(1, 'data');
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

        $u1 = User::factory()->create();
        $u1->workshops()->attach($w1);

        $u2 = User::factory()->create();
        $u2->workshops()->attach($w2);

        $this->actingAs($sa, 'sanctum')
             ->getJson("/api/users?workshop_id={$w1->id}")
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.id', $u1->id);
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

    // ── response includes workshops ──────────────────────────────────────────

    public function test_response_includes_user_workshops(): void
    {
        $sa = $this->superAdmin();
        $workshop = Workshop::factory()->create(['name' => 'UNION DEL PLATA', 'number' => 1]);
        $user = User::factory()->create();
        $user->workshops()->attach($workshop);

        $response = $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users')
             ->assertOk();

        $userData = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertNotEmpty($userData['workshops']);
        $this->assertEquals('UNION DEL PLATA', $userData['workshops'][0]['name']);
    }

    // ── update status ────────────────────────────────────────────────────────

    public function test_superadmin_can_change_user_status(): void
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

    public function test_admin_can_change_status_of_own_workshop_member(): void
    {
        $admin = $this->admin();
        $workshop = Workshop::factory()->create();
        $admin->workshops()->attach($workshop);

        $member = User::factory()->pending()->create();
        $member->workshops()->attach($workshop);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$member->id}/status", ['status' => 'active'])
             ->assertOk()
             ->assertJsonPath('data.status', 'active');
    }

    public function test_admin_cannot_change_status_of_other_workshop_member(): void
    {
        $admin = $this->admin();
        $workshop1 = Workshop::factory()->create();
        $admin->workshops()->attach($workshop1);

        $workshop2 = Workshop::factory()->create();
        $outsider = User::factory()->create();
        $outsider->workshops()->attach($workshop2);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$outsider->id}/status", ['status' => 'suspended'])
             ->assertForbidden();
    }

    public function test_admin_cannot_change_own_status(): void
    {
        $admin = $this->admin();
        $workshop = Workshop::factory()->create();
        $admin->workshops()->attach($workshop);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/users/{$admin->id}/status", ['status' => 'inactive'])
             ->assertForbidden();
    }

    public function test_regular_user_cannot_change_status(): void
    {
        $user = $this->regularUser();
        $target = User::factory()->create();

        $this->actingAs($user, 'sanctum')
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

        $fresh = $user->fresh();
        $this->assertEquals('active', $fresh->status->value);
        $this->assertNull($fresh->email_verified_at);
    }

    public function test_invalid_status_rejected(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->create();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}/status", ['status' => 'invalid'])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['status']);
    }

    public function test_unauthenticated_cannot_change_status(): void
    {
        $user = User::factory()->create();

        $this->patchJson("/api/users/{$user->id}/status", ['status' => 'active'])
             ->assertUnauthorized();
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
}
