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

    public function test_user_without_admin_role_cannot_list_users(): void
    {
        // /users es payload administrativo (incluye email): mínimo dato exige
        // que la comunidad consulte Hermanos por /people.
        $this->actingAs($this->user(), 'sanctum')
             ->getJson('/api/users')
             ->assertForbidden();
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

    public function test_workshop_admin_cannot_list_users(): void
    {
        // La pantalla de Hermanos es exclusiva del Superadmin: el Admin de Taller
        // resuelve sus tareas en Administración → Validaciones, no acá.
        $workshop = Workshop::factory()->create();
        $admin = $this->workshopAdmin($workshop);
        $this->workshopMember($workshop);

        $this->actingAs($admin, 'sanctum')
             ->getJson('/api/users')
             ->assertForbidden();
    }

    public function test_regular_workshop_member_cannot_list_users(): void
    {
        // Un miembro común (sin rol de admin en ningún taller) no accede al
        // listado administrativo; su vista comunitaria es /people.
        $workshop = Workshop::factory()->create();
        $user = $this->workshopMember($workshop);
        $this->workshopMember($workshop);

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/users')
             ->assertForbidden();
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

    public function test_superadmin_admin_search_matches_masonic_id(): void
    {
        $sa = $this->superAdmin();
        $target = User::factory()->create(['name' => 'Sin Coincidencia', 'masonic_id' => 123456]);
        User::factory()->create(['name' => 'Otro Usuario', 'masonic_id' => 654321]);

        $response = $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?search=123456')
             ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
        $this->assertCount(1, $ids);
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

    public function test_filter_by_province(): void
    {
        $sa = $this->superAdmin();
        $target = User::factory()->create(['province' => 'Buenos Aires']);
        User::factory()->create(['province' => 'Córdoba']);

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?province=' . urlencode('Buenos Aires'))
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_search_matches_last_name(): void
    {
        $sa = $this->superAdmin();
        $target = User::factory()->create(['name' => 'Juan', 'last_name' => 'Gerling']);
        User::factory()->create(['name' => 'Otro', 'last_name' => 'Perez']);

        $response = $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?search=gerling')
             ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
        $this->assertCount(1, $ids);
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

    public function test_sort_by_last_name(): void
    {
        $sa = $this->superAdmin();
        User::factory()->create(['last_name' => 'Zabala']);
        User::factory()->create(['last_name' => 'Alvarez']);

        $response = $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?sort_by=last_name&sort_direction=asc')
             ->assertOk();

        $lastNames = collect($response->json('data'))->pluck('last_name')->filter()->values()->toArray();
        $this->assertEquals($lastNames, collect($lastNames)->sort()->values()->toArray());
    }

    public function test_sort_by_workshops_and_actions_is_accepted(): void
    {
        $sa = $this->superAdmin();
        $w = Workshop::factory()->create(['number' => 7]);
        $this->workshopMember($w);

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?sort_by=workshops&sort_direction=desc')
             ->assertOk();

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?sort_by=actions&sort_direction=asc')
             ->assertOk();
    }

    public function test_invalid_sort_by_is_rejected(): void
    {
        $sa = $this->superAdmin();

        $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users?sort_by=password')
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['sort_by']);
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

    public function test_response_includes_last_name_and_province(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->create(['last_name' => 'Gerling', 'province' => 'Santa Fe']);

        $response = $this->actingAs($sa, 'sanctum')
             ->getJson('/api/users')
             ->assertOk();

        $userData = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertArrayHasKey('last_name', $userData);
        $this->assertArrayHasKey('province', $userData);
        $this->assertEquals('Gerling', $userData['last_name']);
        $this->assertEquals('Santa Fe', $userData['province']);
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

    public function test_activating_user_without_verified_email_seals_verification(): void
    {
        $sa = $this->superAdmin();
        $user = User::factory()->pending()->unverified()->create();

        $this->actingAs($sa, 'sanctum')
             ->patchJson("/api/users/{$user->id}/status", ['status' => 'active'])
             ->assertOk()
             ->assertJsonPath('data.status', 'active');

        // Al activar manualmente un usuario que aún no verificó su email,
        // se lo da por validado y se sella la fecha de verificación.
        $this->assertNotNull($user->fresh()->email_verified_at);
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
