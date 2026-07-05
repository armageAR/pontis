<?php

namespace Tests\Feature;

use App\Models\PontisNotification;
use App\Models\Service;
use App\Models\User;
use App\Models\UserVisibilitySetting;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MinimumDataPayloadsTest extends TestCase
{
    use RefreshDatabase;

    private function member(Workshop $workshop, bool $principal = true, string $role = 'member'): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->workshops()->attach($workshop->id, ['role' => $role, 'is_principal' => $principal]);
        return $user;
    }

    /** Claves sensibles que nunca deben aparecer en payloads comunitarios. */
    private const FORBIDDEN_KEYS = ['email', 'phone', 'whatsapp', 'dni'];

    private function assertNoSensitiveKeys(array $row, string $context): void
    {
        foreach (self::FORBIDDEN_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $row, "payload comunitario de {$context} no debe incluir {$key}");
        }
    }

    // ── 3.1 Roster y búsqueda de Hermanos ────────────────────────────────────

    public function test_people_roster_has_no_sensitive_keys_and_gates_masonic_fields(): void
    {
        $principalWorkshop = Workshop::factory()->create();
        $sharedWorkshop = Workshop::factory()->create();

        // El subject tiene su taller principal aparte y comparte uno secundario
        // con el viewer: el bloque masónico (default: taller principal) no es
        // visible para el viewer.
        $subject = $this->member($principalWorkshop, principal: true);
        $subject->workshops()->attach($sharedWorkshop->id, ['role' => 'member', 'is_principal' => false]);
        $subject->update(['masonic_id' => 777, 'masonic_status' => 'active', 'dni' => '12345678', 'phone' => '111']);

        $viewer = $this->member($sharedWorkshop, principal: true);

        $rows = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?per_page=50')->assertOk()->json('data');
        $row = collect($rows)->firstWhere('id', $subject->id);

        $this->assertNotNull($row);
        $this->assertNoSensitiveKeys($row, '/people roster');
        // Identidad default 'workshop' (principal): viewer no califica → matrícula oculta.
        $this->assertNull($row['masonic_id']);
        // Bloque masónico default 'workshop': viewer no califica → estado oculto.
        $this->assertNull($row['masonic_status']);
    }

    public function test_people_roster_shows_masonic_fields_to_principal_workshop_peers(): void
    {
        $workshop = Workshop::factory()->create();
        $subject = $this->member($workshop, principal: true);
        $subject->update(['masonic_id' => 777, 'masonic_status' => 'active']);
        $viewer = $this->member($workshop, principal: true);

        $rows = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?per_page=50')->assertOk()->json('data');
        $row = collect($rows)->firstWhere('id', $subject->id);

        $this->assertSame(777, (int) $row['masonic_id']);
        $this->assertSame('active', $row['masonic_status']);
    }

    public function test_people_search_has_no_sensitive_keys(): void
    {
        $workshop = Workshop::factory()->create();
        $subject = $this->member($workshop);
        $subject->update(['phone' => '111', 'dni' => '999']);
        UserVisibilitySetting::create(['user_id' => $subject->id, 'block' => 'identity', 'visibility' => 'registered', 'anonymous_search' => false]);
        $viewer = $this->member(Workshop::factory()->create());

        $rows = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/people?scope=search&workshop_id=' . $workshop->id)->assertOk()->json('data');
        $row = collect($rows)->firstWhere('id', $subject->id);

        $this->assertNotNull($row);
        $this->assertNoSensitiveKeys($row, '/people búsqueda');
    }

    public function test_public_profile_never_returns_email_or_dni(): void
    {
        $subject = $this->member(Workshop::factory()->create());
        $subject->update(['dni' => '12345678']);
        // Incluso con todos los bloques abiertos, email y DNI no forman parte
        // del payload comunitario de la ficha.
        foreach (['identity', 'masonic', 'contact', 'location', 'profession', 'bio'] as $block) {
            UserVisibilitySetting::create(['user_id' => $subject->id, 'block' => $block, 'visibility' => 'registered', 'anonymous_search' => false]);
        }
        $viewer = $this->member(Workshop::factory()->create());

        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/people/{$subject->id}")->assertOk()->json();

        $this->assertArrayNotHasKey('email', $res);
        $this->assertArrayNotHasKey('dni', $res);
    }

    // ── 3.1 Explore ───────────────────────────────────────────────────────────

    public function test_explore_service_author_has_no_sensitive_keys(): void
    {
        $this->markTestSkipped('Publicaciones diferidas a V2 (defer-publications-to-v2).');
        $owner = $this->member(Workshop::factory()->create());
        Service::create([
            'user_id' => $owner->id, 'title' => 'Oferta', 'description' => 'Desc',
            'modality' => 'both', 'visibility' => 'registered', 'status' => 'active',
            'published_at' => Carbon::now()->subDay(), 'expires_at' => Carbon::now()->addDays(30),
        ]);
        $viewer = $this->member(Workshop::factory()->create());

        $rows = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/explore/services')->assertOk()->json('data');

        $this->assertNotEmpty($rows);
        $this->assertNoSensitiveKeys($rows[0]['user'], '/explore autor');
    }

    // ── 3.1 Solicitudes de contacto y notificaciones ─────────────────────────

    public function test_contact_request_payloads_hide_contact_data_until_accept(): void
    {
        $workshop = Workshop::factory()->create();
        $requestee = $this->member($workshop);
        $requestee->update(['phone' => '111', 'whatsapp' => '222']);
        $requester = $this->member($workshop);

        $created = $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, necesito contactarte.',
            'reason_type'  => 'other',
        ])->assertCreated()->json();

        // Pendiente: sin datos de contacto de ninguna de las partes.
        $this->assertNoSensitiveKeys($created['requestee'], 'contacto pendiente (requestee)');
        $this->assertNoSensitiveKeys($created['requester'], 'contacto pendiente (requester)');

        // Aceptar es el consentimiento que habilita los datos de contacto.
        $accepted = $this->actingAs($requestee, 'sanctum')
            ->postJson("/api/contact-requests/{$created['id']}/accept")->assertOk()->json();
        $this->assertArrayHasKey('email', $accepted['requestee']);
        $this->assertArrayHasKey('phone', $accepted['requestee']);
    }

    public function test_contact_notification_contains_no_email(): void
    {
        $workshop = Workshop::factory()->create();
        $requestee = $this->member($workshop);
        $requester = $this->member($workshop);

        $this->actingAs($requester, 'sanctum')->postJson('/api/contact-requests', [
            'requestee_id' => $requestee->id,
            'message'      => 'Hola, necesito contactarte.',
            'reason_type'  => 'other',
        ])->assertCreated();

        $notification = PontisNotification::where('user_id', $requestee->id)
            ->where('type', 'contact_received')->first();
        $this->assertNotNull($notification);
        $this->assertStringNotContainsString($requester->email, $notification->body);
        $this->assertStringNotContainsString('@', json_encode($notification->data));
    }

    // ── 3.2 Contexto administrativo conserva sus campos ──────────────────────

    public function test_admin_user_list_includes_email_for_superadmin(): void
    {
        // La pantalla de Hermanos es exclusiva del Superadmin, pero conserva los
        // campos administrativos (email, verificación) en su payload.
        $workshop = Workshop::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
        $member = $this->member($workshop);

        $rows = $this->actingAs($superadmin, 'sanctum')
            ->getJson('/api/users')->assertOk()->json('data');
        $row = collect($rows)->firstWhere('id', $member->id);

        $this->assertNotNull($row);
        $this->assertSame($member->email, $row['email']);
        $this->assertArrayHasKey('email_verified_at', $row);
    }

    public function test_workshop_members_endpoint_separates_admin_and_community_payloads(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, role: 'admin');
        $member = $this->member($workshop);
        $peer = $this->member($workshop);

        // Admin del taller: payload administrativo con email.
        $rows = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}/users")->assertOk()->json('data');
        $this->assertArrayHasKey('email', collect($rows)->firstWhere('id', $member->id));

        // Miembro común: mínimo dato, sin email.
        $rows = $this->actingAs($peer, 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}/users")->assertOk()->json('data');
        $row = collect($rows)->firstWhere('id', $member->id);
        $this->assertNotNull($row);
        $this->assertNoSensitiveKeys($row, 'miembros de taller (miembro común)');
        $this->assertArrayHasKey('workshop_role', $row);
    }

    public function test_workshop_show_include_users_only_for_admin_context(): void
    {
        $workshop = Workshop::factory()->create();
        $admin = $this->member($workshop, role: 'admin');
        $member = $this->member($workshop);

        $res = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}?include=users")->assertOk()->json('data');
        $this->assertArrayHasKey('users', $res);

        $res = $this->actingAs($member, 'sanctum')
            ->getJson("/api/admin/workshops/{$workshop->id}?include=users")->assertOk()->json('data');
        $this->assertArrayNotHasKey('users', $res);
    }
}
