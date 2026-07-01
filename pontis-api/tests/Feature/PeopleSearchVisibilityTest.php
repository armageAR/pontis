<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserVisibilitySetting;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleSearchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, bool $principal = true): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => 'member', 'is_principal' => $principal]);
        return $user;
    }

    private function setIdentity(User $user, string $visibility, bool $anonymousSearch): void
    {
        UserVisibilitySetting::create([
            'user_id'          => $user->id,
            'block'            => 'identity',
            'visibility'       => $visibility,
            'anonymous_search' => $anonymousSearch,
        ]);
    }

    /** 4.1 Qualified viewer sees identity even with anonymous-search enabled. */
    public function test_qualified_viewer_sees_identity_even_with_anonymous_search_enabled(): void
    {
        $workshop = Workshop::factory()->create();
        $subject = $this->member($workshop);
        $subject->update(['name' => 'Juan', 'last_name' => 'Perez', 'masonic_id' => 555]);
        $this->setIdentity($subject, 'my_workshops', true);

        $viewer = $this->member($workshop); // comparte taller → califica

        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?scope=search&workshop_id=' . $workshop->id)
            ->assertOk()
            ->json();

        $found = collect($res['data'])->firstWhere('id', $subject->id);
        $this->assertNotNull($found);
        $this->assertFalse($found['anonymous']);
        $this->assertSame('Juan', $found['name']);
        $this->assertSame('Perez', $found['last_name']);
    }

    /** 4.2 Non-qualified viewer sees anonymous result when anonymous-search enabled and query uses eligible non-identity criteria. */
    public function test_non_qualified_viewer_sees_anonymous_result_when_enabled(): void
    {
        $workshopA = Workshop::factory()->create();
        $workshopB = Workshop::factory()->create();

        $subject = $this->member($workshopA);
        $subject->update(['name' => 'Juan', 'last_name' => 'Perez', 'masonic_id' => 555]);
        $this->setIdentity($subject, 'my_workshops', true);

        $viewer = $this->member($workshopB); // no comparte taller → no califica

        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?scope=search&workshop_id=' . $workshopA->id)
            ->assertOk()
            ->json();

        $found = collect($res['data'])->firstWhere('id', $subject->id);
        $this->assertNotNull($found);
        $this->assertTrue($found['anonymous']);
        $this->assertSame('Hermano registrado', $found['name']);
        $this->assertNull($found['last_name']);
        $this->assertNull($found['masonic_id']);
    }

    /** 4.2b Non-qualified viewer without anonymous-search does not appear. */
    public function test_non_qualified_viewer_does_not_see_subject_when_anonymous_search_disabled(): void
    {
        $workshopA = Workshop::factory()->create();
        $workshopB = Workshop::factory()->create();

        $subject = $this->member($workshopA);
        $this->setIdentity($subject, 'my_workshops', false);

        $viewer = $this->member($workshopB);

        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?scope=search&workshop_id=' . $workshopA->id)
            ->assertOk()
            ->json();

        $this->assertNull(collect($res['data'])->firstWhere('id', $subject->id));
    }

    /** 4.3 Hidden identity fields are not searchable, even with anonymous-search enabled. */
    public function test_non_qualified_viewer_does_not_match_by_hidden_identity_fields(): void
    {
        $workshopA = Workshop::factory()->create();
        $workshopB = Workshop::factory()->create();

        $subject = $this->member($workshopA);
        $subject->update(['name' => 'Zoltan', 'last_name' => 'Kovacs', 'masonic_id' => 777]);
        $this->setIdentity($subject, 'my_workshops', true);

        $viewer = $this->member($workshopB); // no califica

        // Búsqueda por nombre: no debe matchear campos de identidad ocultos.
        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?scope=search&q=Zoltan')
            ->assertOk()
            ->json();

        $this->assertNull(collect($res['data'])->firstWhere('id', $subject->id));
    }
}
