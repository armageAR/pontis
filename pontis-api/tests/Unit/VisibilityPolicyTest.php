<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserVisibilitySetting;
use App\Models\Workshop;
use App\Support\VisibilityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, bool $principal = true): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => 'member', 'is_principal' => $principal]);
        return $user;
    }

    private function setBlock(User $user, string $block, string $visibility, bool $anonymousSearch = false): void
    {
        UserVisibilitySetting::create([
            'user_id'          => $user->id,
            'block'            => $block,
            'visibility'       => $visibility,
            'anonymous_search' => $anonymousSearch,
        ]);
    }

    // ── 2.1 Matriz de decisión núcleo ─────────────────────────────────────────

    public function test_can_see_matrix_by_level_and_relation(): void
    {
        $shared = Workshop::factory()->create();
        $other  = Workshop::factory()->create();

        $viewer = $this->member($shared, principal: false);
        $policy = new VisibilityPolicy($viewer);

        $subjectId = 999999; // distinto del viewer

        // private: nunca
        $this->assertFalse($policy->canSee('private', $subjectId, [$shared->id], $shared->id));
        // registered: siempre
        $this->assertTrue($policy->canSee('registered', $subjectId, [$other->id], $other->id));
        // my_workshops: requiere compartir algún taller
        $this->assertTrue($policy->canSee('my_workshops', $subjectId, [$shared->id], $other->id));
        $this->assertFalse($policy->canSee('my_workshops', $subjectId, [$other->id], $other->id));
        // workshop: requiere estar en el taller principal del subject
        $this->assertTrue($policy->canSee('workshop', $subjectId, [$shared->id, $other->id], $shared->id));
        $this->assertFalse($policy->canSee('workshop', $subjectId, [$shared->id, $other->id], $other->id));
        // anonymous: la identidad nunca se revela por nombre
        $this->assertFalse($policy->canSee('anonymous', $subjectId, [$shared->id], $shared->id));
        // nivel desconocido: default = taller principal
        $this->assertTrue($policy->canSee('desconocido', $subjectId, [$shared->id], $shared->id));
        $this->assertFalse($policy->canSee('desconocido', $subjectId, [$other->id], $other->id));
    }

    public function test_self_always_sees_own_blocks(): void
    {
        $workshop = Workshop::factory()->create();
        $viewer = $this->member($workshop);
        $policy = new VisibilityPolicy($viewer);

        $this->assertTrue($policy->canSee('private', $viewer->id, [], null));
        $this->assertTrue($policy->identityFor($viewer)->visible);
    }

    public function test_block_level_defaults_to_workshop(): void
    {
        $this->assertSame('workshop', VisibilityPolicy::blockLevel(collect(), 'contact'));
        $this->assertSame('workshop', VisibilityPolicy::blockLevel(null, 'contact'));
    }

    // ── Identidad en dos pasos ────────────────────────────────────────────────

    public function test_identity_visible_for_qualified_viewer(): void
    {
        $workshop = Workshop::factory()->create();
        $subject = $this->member($workshop);
        $viewer  = $this->member($workshop);
        $this->setBlock($subject, 'identity', 'my_workshops', anonymousSearch: true);

        $identity = (new VisibilityPolicy($viewer))->identityFor($subject);
        $this->assertTrue($identity->visible);
        $this->assertFalse($identity->anonymous);
    }

    public function test_identity_anonymous_for_non_qualified_viewer_with_flag(): void
    {
        $subject = $this->member(Workshop::factory()->create());
        $viewer  = $this->member(Workshop::factory()->create());
        $this->setBlock($subject, 'identity', 'private', anonymousSearch: true);

        $identity = (new VisibilityPolicy($viewer))->identityFor($subject);
        $this->assertFalse($identity->visible);
        $this->assertTrue($identity->anonymous);
        $this->assertSame(VisibilityPolicy::MASKED_NAME, (new VisibilityPolicy($viewer))->displayName($subject));
    }

    public function test_identity_hidden_entirely_without_anonymous_flag(): void
    {
        $subject = $this->member(Workshop::factory()->create());
        $viewer  = $this->member(Workshop::factory()->create());
        $this->setBlock($subject, 'identity', 'private', anonymousSearch: false);

        $identity = (new VisibilityPolicy($viewer))->identityFor($subject);
        $this->assertFalse($identity->visible);
        $this->assertFalse($identity->anonymous);
    }

    // ── Vista previa de publicación ───────────────────────────────────────────

    public function test_publication_preview_masks_identity_when_anonymous(): void
    {
        $author = User::factory()->create(['name' => 'Juan', 'last_name' => 'Perez', 'profession' => 'Abogado']);

        $preview = VisibilityPolicy::publicationPreview($author, 'anonymous');
        $this->assertTrue($preview['anonymous']);
        $this->assertSame(VisibilityPolicy::MASKED_NAME, $preview['author']['name']);
        $this->assertNull($preview['author']['last_name']);
        $this->assertSame('Abogado', $preview['author']['profession']);
    }

    public function test_publication_preview_shows_identity_and_audience_otherwise(): void
    {
        $author = User::factory()->create(['name' => 'Juan', 'last_name' => 'Perez']);

        $preview = VisibilityPolicy::publicationPreview($author, 'registered');
        $this->assertFalse($preview['anonymous']);
        $this->assertSame('Juan', $preview['author']['name']);
        $this->assertSame('Perez', $preview['author']['last_name']);
        $this->assertNotEmpty($preview['audience']);
    }
}
