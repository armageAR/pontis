<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use App\Models\UserPosition;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileWorkshopsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function attach(User $user, Workshop $workshop, string $status = 'active', bool $principal = false, string $role = 'member'): void
    {
        $user->workshopMemberships()->attach($workshop->id, [
            'role'         => $role,
            'status'       => $status,
            'is_principal' => $principal,
        ]);
    }

    // ── 5.1 Listing ───────────────────────────────────────────────────────────

    public function test_profile_lists_active_and_pending_memberships(): void
    {
        $user = $this->user();
        $active = Workshop::factory()->create();
        $pending = Workshop::factory()->create();
        $rejected = Workshop::factory()->create();

        $this->attach($user, $active, 'active', true);
        $this->attach($user, $pending, 'pending');
        $this->attach($user, $rejected, 'rejected');

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/profile/workshops')->assertOk()->json();

        $ids = collect($res)->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertTrue($ids->contains($pending->id));
        $this->assertFalse($ids->contains($rejected->id), 'rejected memberships must not be listed');

        $activeRow = collect($res)->firstWhere('id', $active->id);
        $this->assertSame('active', $activeRow['status']);
        $this->assertTrue($activeRow['is_principal']);
    }

    // ── 5.2 Join / duplicate prevention ──────────────────────────────────────

    public function test_profile_join_creates_pending_request(): void
    {
        $user = $this->user();
        $workshop = Workshop::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/join")
            ->assertOk();

        $this->assertDatabaseHas('user_workshop', [
            'user_id'     => $user->id,
            'workshop_id' => $workshop->id,
            'status'      => 'pending',
        ]);
    }

    public function test_profile_join_does_not_duplicate_existing_pending(): void
    {
        $user = $this->user();
        $workshop = Workshop::factory()->create();
        $this->attach($user, $workshop, 'pending');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admin/workshops/{$workshop->id}/join")
            ->assertOk();

        $count = DB::table('user_workshop')
            ->where('user_id', $user->id)
            ->where('workshop_id', $workshop->id)
            ->count();
        $this->assertSame(1, $count);
    }

    // ── 5.3 Reject leaving principal ─────────────────────────────────────────

    public function test_cannot_leave_principal_workshop(): void
    {
        $user = $this->user();
        $principal = Workshop::factory()->create();
        $this->attach($user, $principal, 'active', true);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/admin/workshops/{$principal->id}/leave")
            ->assertStatus(422);

        $this->assertDatabaseHas('user_workshop', [
            'user_id'      => $user->id,
            'workshop_id'  => $principal->id,
            'is_principal' => true,
        ]);
    }

    // ── 5.4 Leaving removes membership and cargos ────────────────────────────

    public function test_leaving_non_principal_workshop_removes_membership_and_positions(): void
    {
        $user = $this->user();
        $principal = Workshop::factory()->create();
        $other = Workshop::factory()->create();
        $this->attach($user, $principal, 'active', true);
        $this->attach($user, $other, 'active');

        $position = Position::create(['name' => 'Venerable Maestro']);
        UserPosition::create([
            'user_id'     => $user->id,
            'position_id' => $position->id,
            'workshop_id' => $other->id,
            'start_date'  => '2026-01-01',
        ]);
        // Cargo en el taller principal: no debe borrarse.
        UserPosition::create([
            'user_id'     => $user->id,
            'position_id' => $position->id,
            'workshop_id' => $principal->id,
            'start_date'  => '2026-01-01',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/admin/workshops/{$other->id}/leave")
            ->assertOk();

        $this->assertDatabaseMissing('user_workshop', [
            'user_id'     => $user->id,
            'workshop_id' => $other->id,
        ]);
        $this->assertDatabaseMissing('user_positions', [
            'user_id'     => $user->id,
            'workshop_id' => $other->id,
        ]);
        // El cargo del taller principal permanece.
        $this->assertDatabaseHas('user_positions', [
            'user_id'     => $user->id,
            'workshop_id' => $principal->id,
        ]);
    }

    public function test_leaving_workshop_without_positions_succeeds(): void
    {
        $user = $this->user();
        $principal = Workshop::factory()->create();
        $other = Workshop::factory()->create();
        $this->attach($user, $principal, 'active', true);
        $this->attach($user, $other, 'active');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/admin/workshops/{$other->id}/leave")
            ->assertOk();

        $this->assertDatabaseMissing('user_workshop', [
            'user_id'     => $user->id,
            'workshop_id' => $other->id,
        ]);
    }

    // ── 5.5 Atomic principal change / rejection ──────────────────────────────

    public function test_set_principal_moves_flag_atomically(): void
    {
        $user = $this->user();
        $current = Workshop::factory()->create();
        $target = Workshop::factory()->create();
        $this->attach($user, $current, 'active', true);
        $this->attach($user, $target, 'active');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/profile/workshops/{$target->id}/principal")
            ->assertOk();

        // Exactamente un principal activo, y es el nuevo.
        $principals = DB::table('user_workshop')
            ->where('user_id', $user->id)
            ->where('is_principal', true)
            ->pluck('workshop_id');
        $this->assertCount(1, $principals);
        $this->assertSame($target->id, $principals->first());
    }

    public function test_cannot_set_pending_membership_as_principal(): void
    {
        $user = $this->user();
        $current = Workshop::factory()->create();
        $pending = Workshop::factory()->create();
        $this->attach($user, $current, 'active', true);
        $this->attach($user, $pending, 'pending');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/profile/workshops/{$pending->id}/principal")
            ->assertStatus(422);

        $this->assertDatabaseHas('user_workshop', [
            'user_id'      => $user->id,
            'workshop_id'  => $current->id,
            'is_principal' => true,
        ]);
        $this->assertDatabaseHas('user_workshop', [
            'user_id'      => $user->id,
            'workshop_id'  => $pending->id,
            'is_principal' => false,
        ]);
    }

    public function test_cannot_set_principal_without_active_membership(): void
    {
        $user = $this->user();
        $current = Workshop::factory()->create();
        $foreign = Workshop::factory()->create();
        $this->attach($user, $current, 'active', true);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/profile/workshops/{$foreign->id}/principal")
            ->assertStatus(422);

        $this->assertDatabaseHas('user_workshop', [
            'user_id'      => $user->id,
            'workshop_id'  => $current->id,
            'is_principal' => true,
        ]);
    }
}
