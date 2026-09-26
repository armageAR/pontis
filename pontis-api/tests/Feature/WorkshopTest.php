<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class WorkshopTest extends TestCase
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

    // ── Auth: unauthenticated ────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_workshops(): void
    {
        $this->getJson('/api/admin/workshops')->assertUnauthorized();
        $this->postJson('/api/admin/workshops')->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_workshop_detail(): void
    {
        $workshop = Workshop::factory()->create();

        $this->getJson("/api/admin/workshops/{$workshop->id}")->assertUnauthorized();
        $this->patchJson("/api/admin/workshops/{$workshop->id}")->assertUnauthorized();
        $this->deleteJson("/api/admin/workshops/{$workshop->id}")->assertUnauthorized();
        $this->postJson("/api/admin/workshops/{$workshop->id}/disable")->assertUnauthorized();
        $this->postJson("/api/admin/workshops/{$workshop->id}/enable")->assertUnauthorized();
        $this->getJson("/api/admin/workshops/{$workshop->id}/users")->assertUnauthorized();
        $this->postJson("/api/admin/workshops/{$workshop->id}/users")->assertUnauthorized();
        $this->deleteJson("/api/admin/workshops/{$workshop->id}/users")->assertUnauthorized();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_superadmin_can_list_all_workshops(): void
    {
        Workshop::factory()->count(3)->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Group('known-failure')] // See issue #1: the listing is not scoped by membership.
    public function test_user_sees_only_own_workshops(): void
    {
        $workshop = Workshop::factory()->create();
        $other = Workshop::factory()->create();
        $user = $this->workshopMember($workshop);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/workshops')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $workshop->id);

        unset($other);
    }

    #[Group('known-failure')] // See issue #1: the listing is not scoped by membership.
    public function test_user_with_no_workshops_sees_empty_list(): void
    {
        Workshop::factory()->count(2)->create();

        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/admin/workshops')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Group('known-failure')] // See issue #1: the listing is not scoped by membership.
    public function test_workshop_admin_sees_only_own_workshops(): void
    {
        $workshop = Workshop::factory()->create();
        Workshop::factory()->create();

        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/workshops')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $workshop->id);
    }

    public function test_filter_by_search(): void
    {
        Workshop::factory()->create(['name' => 'UNION DEL PLATA', 'zone_name' => 'Zona 1', 'address' => 'Calle 1', 'city' => 'CABA']);
        Workshop::factory()->create(['name' => 'CONFRATERNIDAD', 'zone_name' => 'Zona 1', 'address' => 'Calle 2', 'city' => 'CABA']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?search=UNION')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'UNION DEL PLATA');
    }

    public function test_filter_by_zone_number(): void
    {
        Workshop::factory()->create(['zone_number' => 1]);
        Workshop::factory()->create(['zone_number' => 2]);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?zone_number=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_status(): void
    {
        Workshop::factory()->create(['status' => 'active']);
        Workshop::factory()->create(['status' => 'disabled']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?status=active')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_work_day(): void
    {
        Workshop::factory()->create(['work_day' => 'Lunes']);
        Workshop::factory()->create(['work_day' => 'Martes']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?work_day=Lunes')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_city(): void
    {
        Workshop::factory()->create(['city' => 'CABA']);
        Workshop::factory()->create(['city' => 'Rosario']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?city=CABA')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_province(): void
    {
        Workshop::factory()->create(['province' => 'Buenos Aires']);
        Workshop::factory()->create(['province' => 'Córdoba']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?province=Buenos+Aires')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_pagination_respects_per_page(): void
    {
        Workshop::factory()->count(5)->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_order_by_number(): void
    {
        Workshop::factory()->create(['number' => 5]);
        Workshop::factory()->create(['number' => 1]);
        Workshop::factory()->create(['number' => 3]);

        $response = $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson('/api/admin/workshops?sort_by=number&sort_direction=asc')
            ->assertOk();

        $numbers = collect($response->json('data'))->pluck('number')->toArray();
        $this->assertEquals([1, 3, 5], $numbers);
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_superadmin_can_create_workshop(): void
    {
        $payload = [
            'name' => 'UNION DEL PLATA',
            'number' => 1,
            'zone_number' => 1,
            'zone_name' => 'Logias de CABA',
            'work_day' => 'Lunes',
            'work_frequency' => '1ro 3ro 5to',
            'address' => 'TTE. GRAL. J. D. PERON 1242',
            'city' => 'CABA',
            'province' => 'Ciudad Autónoma de Buenos Aires',
            'country' => 'Argentina',
        ];

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson('/api/admin/workshops', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'UNION DEL PLATA')
            ->assertJsonPath('data.number', 1);

        $this->assertDatabaseHas('workshops', ['name' => 'UNION DEL PLATA', 'number' => 1]);
    }

    public function test_workshop_admin_cannot_create_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/workshops', ['name' => 'Test', 'number' => 99])
            ->assertForbidden();
    }

    public function test_user_cannot_create_workshop(): void
    {
        $this->actingAs($this->user(), 'sanctum')
            ->postJson('/api/admin/workshops', ['name' => 'Test', 'number' => 99])
            ->assertForbidden();
    }

    public function test_validation_fails_without_name(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson('/api/admin/workshops', ['number' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_fails_without_number(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson('/api/admin/workshops', ['name' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['number']);
    }

    public function test_validation_fails_with_duplicate_number(): void
    {
        Workshop::factory()->create(['number' => 1]);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson('/api/admin/workshops', ['name' => 'Another', 'number' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['number']);
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function test_superadmin_can_view_any_workshop(): void
    {
        $workshop = Workshop::factory()->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $workshop->id);
    }

    public function test_member_can_view_own_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $user = $this->workshopMember($workshop);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $workshop->id);
    }

    public function test_workshop_admin_can_view_own_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $workshop->id);
    }

    public function test_user_cannot_view_unassigned_workshop(): void
    {
        $workshop = Workshop::factory()->create();

        $this->actingAs($this->user(), 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}")
            ->assertForbidden();
    }

    public function test_show_with_include_users(): void
    {
        $workshop = Workshop::factory()->create();
        $users = User::factory()->count(2)->create();
        $workshop->users()->attach($users->pluck('id'));

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}?include=users")
            ->assertOk()
            ->assertJsonCount(2, 'data.users');
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_superadmin_can_update_any_workshop(): void
    {
        $workshop = Workshop::factory()->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->patchJson("/api/admin/workshops/{$workshop->id}", ['name' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('workshops', ['id' => $workshop->id, 'name' => 'Updated']);
    }

    public function test_workshop_admin_can_update_own_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/workshops/{$workshop->id}", ['name' => 'Admin Updated'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Admin Updated');
    }

    public function test_workshop_admin_cannot_update_other_workshop(): void
    {
        $myWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($myWorkshop);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/workshops/{$otherWorkshop->id}", ['name' => 'Hack'])
            ->assertForbidden();
    }

    public function test_member_cannot_update_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);

        $this->actingAs($member, 'sanctum')
            ->patchJson("/api/admin/workshops/{$workshop->id}", ['name' => 'Hack'])
            ->assertForbidden();
    }

    public function test_update_number_unique_ignores_current(): void
    {
        $workshop = Workshop::factory()->create(['number' => 10]);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->patchJson("/api/admin/workshops/{$workshop->id}", ['number' => 10])
            ->assertOk();
    }

    public function test_update_number_unique_rejects_duplicate(): void
    {
        Workshop::factory()->create(['number' => 10]);
        $workshop = Workshop::factory()->create(['number' => 20]);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->patchJson("/api/admin/workshops/{$workshop->id}", ['number' => 10])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['number']);
    }

    // ── Disable ──────────────────────────────────────────────────────────────

    public function test_superadmin_can_disable_workshop(): void
    {
        $workshop = Workshop::factory()->create(['status' => 'active']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/disable")
            ->assertOk()
            ->assertJsonPath('data.status', 'disabled');

        $this->assertDatabaseHas('workshops', ['id' => $workshop->id, 'status' => 'disabled']);
    }

    public function test_workshop_admin_can_disable_own_workshop(): void
    {
        $workshop = Workshop::factory()->create(['status' => 'active']);
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/disable")
            ->assertOk()
            ->assertJsonPath('data.status', 'disabled');
    }

    public function test_member_cannot_disable_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/disable")
            ->assertForbidden();
    }

    // ── Enable ───────────────────────────────────────────────────────────────

    public function test_superadmin_can_enable_workshop(): void
    {
        $workshop = Workshop::factory()->create(['status' => 'disabled']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/enable")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('workshops', ['id' => $workshop->id, 'status' => 'active']);
    }

    public function test_workshop_admin_can_enable_own_workshop(): void
    {
        $workshop = Workshop::factory()->create(['status' => 'disabled']);
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/enable")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_member_cannot_enable_workshop(): void
    {
        $workshop = Workshop::factory()->create(['status' => 'disabled']);
        $member = $this->workshopMember($workshop);

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/enable")
            ->assertForbidden();
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_superadmin_can_soft_delete_workshop(): void
    {
        $workshop = Workshop::factory()->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->deleteJson("/api/admin/workshops/{$workshop->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('workshops', ['id' => $workshop->id]);
    }

    public function test_delete_uses_soft_delete(): void
    {
        $workshop = Workshop::factory()->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->deleteJson("/api/admin/workshops/{$workshop->id}");

        $this->assertDatabaseHas('workshops', ['id' => $workshop->id]);
        $this->assertNotNull($workshop->fresh()->deleted_at);
    }

    public function test_workshop_admin_cannot_delete_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/workshops/{$workshop->id}")
            ->assertForbidden();
    }

    public function test_member_cannot_delete_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);

        $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/admin/workshops/{$workshop->id}")
            ->assertForbidden();
    }

    // ── Assign Users ─────────────────────────────────────────────────────────

    public function test_superadmin_can_assign_users(): void
    {
        $workshop = Workshop::factory()->create();
        $users = User::factory()->count(3)->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => $users->pluck('id')->toArray(),
            ])
            ->assertOk();

        $this->assertCount(3, $workshop->fresh()->users);
    }

    public function test_workshop_admin_can_assign_users(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $newUser = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$newUser->id],
            ])
            ->assertOk();

        $this->assertTrue($workshop->users()->where('user_id', $newUser->id)->exists());
    }

    public function test_member_cannot_assign_users(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);
        $newUser = User::factory()->create();

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$newUser->id],
            ])
            ->assertForbidden();
    }

    public function test_unassigned_user_cannot_assign_users(): void
    {
        $workshop = Workshop::factory()->create();

        $this->actingAs($this->user(), 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [User::factory()->create()->id],
            ])
            ->assertForbidden();
    }

    public function test_assign_with_admin_role_sets_pivot_role(): void
    {
        $workshop = Workshop::factory()->create();
        $newUser = User::factory()->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$newUser->id],
                'role' => 'admin',
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_workshop', [
            'user_id' => $newUser->id,
            'workshop_id' => $workshop->id,
            'role' => 'admin',
        ]);
    }

    public function test_assign_defaults_to_member_role(): void
    {
        $workshop = Workshop::factory()->create();
        $newUser = User::factory()->create();

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$newUser->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_workshop', [
            'user_id' => $newUser->id,
            'workshop_id' => $workshop->id,
            'role' => 'member',
        ]);
    }

    public function test_assign_users_does_not_duplicate(): void
    {
        $workshop = Workshop::factory()->create();
        $existingUser = User::factory()->create();
        $workshop->users()->attach($existingUser);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$existingUser->id],
            ])
            ->assertOk();

        $this->assertEquals(1, $workshop->users()->where('user_id', $existingUser->id)->count());
    }

    // ── Remove Users ─────────────────────────────────────────────────────────

    public function test_superadmin_can_remove_users(): void
    {
        $workshop = Workshop::factory()->create();
        $users = User::factory()->count(2)->create();
        $workshop->users()->attach($users->pluck('id'));

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->deleteJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$users->first()->id],
            ])
            ->assertOk();

        $this->assertCount(1, $workshop->fresh()->users);
    }

    public function test_workshop_admin_can_remove_users(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $target = User::factory()->create();
        $workshop->users()->attach($target);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$target->id],
            ])
            ->assertOk();

        $this->assertFalse($workshop->users()->where('user_id', $target->id)->exists());
    }

    public function test_member_cannot_remove_users(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);
        $target = User::factory()->create();
        $workshop->users()->attach($target);

        $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/admin/workshops/{$workshop->id}/users", [
                'user_ids' => [$target->id],
            ])
            ->assertForbidden();
    }

    // ── Workshop Users List ──────────────────────────────────────────────────

    public function test_workshop_users_returns_only_assigned(): void
    {
        $workshop = Workshop::factory()->create();
        $assigned = User::factory()->count(2)->create();
        User::factory()->create();

        $workshop->users()->attach($assigned->pluck('id'));

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}/users")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_member_can_list_workshop_users(): void
    {
        $workshop = Workshop::factory()->create();
        $member = $this->workshopMember($workshop);

        $this->actingAs($member, 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}/users")
            ->assertOk();
    }

    public function test_unassigned_user_cannot_list_workshop_users(): void
    {
        $workshop = Workshop::factory()->create();

        $this->actingAs($this->user(), 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}/users")
            ->assertForbidden();
    }

    public function test_workshop_users_filter_by_search(): void
    {
        $workshop = Workshop::factory()->create();
        $u1 = User::factory()->create(['name' => 'Juan Perez']);
        $u2 = User::factory()->create(['name' => 'Maria Lopez']);
        $workshop->users()->attach([$u1->id, $u2->id]);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}/users?search=Juan")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Juan Perez');
    }

    public function test_workshop_users_filter_by_workshop_role(): void
    {
        $workshop = Workshop::factory()->create();
        $adminUser = User::factory()->create();
        $memberUser = User::factory()->create();
        $workshop->users()->attach($adminUser->id, ['role' => 'admin']);
        $workshop->users()->attach($memberUser->id, ['role' => 'member']);

        $this->actingAs($this->superAdmin(), 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}/users?workshop_role=admin")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $adminUser->id);
    }

    public function test_user_can_be_admin_in_one_and_member_in_another(): void
    {
        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        $user = User::factory()->create();

        $user->workshops()->attach($workshop1->id, ['role' => 'admin']);
        $user->workshops()->attach($workshop2->id, ['role' => 'member']);

        $this->assertTrue($user->isAdminOfWorkshop($workshop1));
        $this->assertFalse($user->isAdminOfWorkshop($workshop2));
        $this->assertTrue($user->isAssignedToWorkshop($workshop2));
    }
}
