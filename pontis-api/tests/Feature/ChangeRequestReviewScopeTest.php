<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\PontisNotification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeRequestReviewScopeTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, string $role = 'member', array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'user', 'status' => 'active'], $attrs));
        $user->workshopMemberships()->attach($workshop->id, ['role' => $role, 'status' => 'active', 'is_principal' => true]);
        return $user;
    }

    private function requestFor(User $user, string $field = 'dni', string $newValue = '99999999'): ChangeRequest
    {
        return ChangeRequest::create([
            'user_id'       => $user->id,
            'field'         => $field,
            'current_value' => $user->{$field},
            'new_value'     => $newValue,
            'status'        => 'pending',
        ]);
    }

    public function test_admin_de_taller_lists_only_scoped_requests(): void
    {
        $workshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $owner = $this->member($workshop);
        $outsider = $this->member($otherWorkshop);

        $scoped = $this->requestFor($owner);
        $unrelated = $this->requestFor($outsider);

        $ids = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/change-requests')
            ->assertOk()
            ->json('data'))->pluck('id');

        $this->assertContains($scoped->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_mine_param_returns_only_own_requests_for_admin(): void
    {
        // En Bandeja → Trámites el Admin de Taller usa mine=1 y solo ve lo suyo,
        // aunque como revisor tenga alcance sobre otros Hermanos del Taller.
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $owner = $this->member($workshop);

        $mine = $this->requestFor($admin);
        $scopedButNotMine = $this->requestFor($owner);

        $ids = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/change-requests?mine=1')
            ->assertOk()
            ->json('data'))->pluck('id');

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($scopedButNotMine->id, $ids);
    }

    public function test_superadmin_lists_all_requests(): void
    {
        $workshop = Workshop::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
        $owner = $this->member($workshop);
        $cr = $this->requestFor($owner);

        $this->actingAs($superadmin, 'sanctum')
            ->getJson('/api/change-requests')
            ->assertOk()
            ->assertJsonFragment(['id' => $cr->id]);
    }

    public function test_regular_hermano_lists_only_own_requests(): void
    {
        $workshop = Workshop::factory()->create();
        $owner = $this->member($workshop);
        $other = $this->member($workshop);
        $mine = $this->requestFor($owner);
        $theirs = $this->requestFor($other);

        $ids = collect($this->actingAs($owner, 'sanctum')
            ->getJson('/api/change-requests')
            ->assertOk()
            ->json('data'))->pluck('id');

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_admin_de_taller_approves_scoped_request(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $owner = $this->member($workshop, 'member', ['dni' => '11111111']);
        $cr = $this->requestFor($owner, 'dni', '22222222');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/change-requests/{$cr->id}/approve")
            ->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('reviewer_id', $admin->id);

        $this->assertSame('22222222', $owner->fresh()->dni);
    }

    public function test_admin_de_taller_rejects_scoped_request(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $owner = $this->member($workshop, 'member', ['dni' => '11111111']);
        $cr = $this->requestFor($owner, 'dni', '22222222');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/change-requests/{$cr->id}/reject")
            ->assertOk()
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('reviewer_id', $admin->id);

        $this->assertSame('11111111', $owner->fresh()->dni);
    }

    public function test_admin_de_taller_requires_info_on_scoped_request(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $owner = $this->member($workshop);
        $cr = $this->requestFor($owner);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/change-requests/{$cr->id}/require-info", ['reviewer_notes' => 'Adjuntá el documento'])
            ->assertOk()
            ->assertJsonPath('status', 'requires_info')
            ->assertJsonPath('reviewer_id', $admin->id)
            ->assertJsonPath('reviewer_notes', 'Adjuntá el documento');
    }

    public function test_admin_de_taller_cannot_resolve_unrelated_request(): void
    {
        $workshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $admin = $this->member($workshop, 'admin');
        $outsider = $this->member($otherWorkshop, 'member', ['dni' => '11111111']);
        $cr = $this->requestFor($outsider, 'dni', '22222222');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/change-requests/{$cr->id}/approve")
            ->assertForbidden();

        $this->assertSame('pending', $cr->fresh()->status);
        $this->assertNull($cr->fresh()->reviewer_id);
        $this->assertSame('11111111', $outsider->fresh()->dni);
    }

    public function test_notifications_reach_superadmin_and_scoped_admin_without_duplicates(): void
    {
        $workshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
        $admin = $this->member($workshop, 'admin');
        $unrelatedAdmin = $this->member($otherWorkshop, 'admin');
        $owner = $this->member($workshop, 'member', ['dni' => '11111111']);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/change-requests', ['field' => 'dni', 'new_value' => '22222222'])
            ->assertCreated();

        $recipients = PontisNotification::where('type', 'change_request')->pluck('user_id');

        $this->assertContains($superadmin->id, $recipients->all());
        $this->assertContains($admin->id, $recipients->all());
        $this->assertNotContains($unrelatedAdmin->id, $recipients->all());
        $this->assertNotContains($owner->id, $recipients->all());
        $this->assertSame($recipients->count(), $recipients->unique()->count());
    }

    public function test_admin_who_is_also_superadmin_is_notified_once(): void
    {
        $workshop = Workshop::factory()->create();
        // A superadmin who also holds an admin membership in the owner's workshop.
        $dualRole = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
        $dualRole->workshopMemberships()->attach($workshop->id, ['role' => 'admin', 'status' => 'active']);
        $owner = $this->member($workshop);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/change-requests', ['field' => 'dni', 'new_value' => '22222222'])
            ->assertCreated();

        $this->assertSame(1, PontisNotification::where('type', 'change_request')->where('user_id', $dualRole->id)->count());
    }
}
