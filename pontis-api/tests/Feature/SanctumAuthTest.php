<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    // ── register ─────────────────────────────────────────────────────────────

    public function test_register_creates_user_and_returns_token(): void
    {
        Event::fake([Registered::class]);
        $workshop = Workshop::factory()->create();

        $response = $this->postJson('/api/register', [
            'name'                  => 'Juan Test',
            'email'                 => 'juan@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => $workshop->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'status']]);

        $this->assertDatabaseHas('users', ['email' => 'juan@test.com', 'status' => 'verifying']);
    }

    public function test_register_creates_user_as_verifying(): void
    {
        Event::fake([Registered::class]);
        $workshop = Workshop::factory()->create();

        $response = $this->postJson('/api/register', [
            'name'                  => 'Nuevo User',
            'email'                 => 'nuevo@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => $workshop->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.status', 'verifying');

        $user = User::where('email', 'nuevo@test.com')->first();
        $this->assertEquals('verifying', $user->status->value);
        $this->assertNull($user->email_verified_at);
    }

    public function test_register_attaches_workshop_to_user(): void
    {
        Event::fake([Registered::class]);
        $workshop = Workshop::factory()->create();

        $this->postJson('/api/register', [
            'name'                  => 'Workshop User',
            'email'                 => 'workshop@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => $workshop->id,
        ])->assertStatus(201);

        $user = User::where('email', 'workshop@test.com')->first();
        $this->assertTrue($user->workshopMemberships()->where('workshop_id', $workshop->id)->exists());
        $this->assertDatabaseHas('user_workshop', [
            'user_id'            => $user->id,
            'workshop_id'        => $workshop->id,
            'status'             => 'pending',
            'requested_by_user'  => true,
        ]);
    }

    public function test_register_fails_without_workshop(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'No Workshop',
            'email'                 => 'noworkshop@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['workshop_id']);
    }

    public function test_register_fails_with_invalid_workshop(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'Bad Workshop',
            'email'                 => 'badworkshop@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => 99999,
        ])->assertStatus(422)->assertJsonValidationErrors(['workshop_id']);
    }

    public function test_register_dispatches_registered_event(): void
    {
        Event::fake([Registered::class]);
        $workshop = Workshop::factory()->create();

        $this->postJson('/api/register', [
            'name'                  => 'Event User',
            'email'                 => 'event@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => $workshop->id,
        ]);

        Event::assertDispatched(Registered::class);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'juan@test.com']);
        $workshop = Workshop::factory()->create();

        $this->postJson('/api/register', [
            'name'                  => 'Otro Juan',
            'email'                 => 'juan@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => $workshop->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    // ── workshop search (public) ─────────────────────────────────────────────

    public function test_workshop_search_returns_results(): void
    {
        Workshop::factory()->create(['name' => 'UNION DEL PLATA', 'number' => 1]);
        Workshop::factory()->create(['name' => 'CONFRATERNIDAD', 'number' => 2]);

        $this->getJson('/api/workshops/search?q=union')
             ->assertOk()
             ->assertJsonCount(1)
             ->assertJsonPath('0.name', 'UNION DEL PLATA');
    }

    public function test_workshop_search_by_number(): void
    {
        Workshop::factory()->create(['name' => 'TOLERANCIA', 'number' => 44]);
        Workshop::factory()->create(['name' => 'OTHER', 'number' => 55]);

        $this->getJson('/api/workshops/search?q=44')
             ->assertOk()
             ->assertJsonCount(1)
             ->assertJsonPath('0.number', 44);
    }

    public function test_workshop_search_requires_min_2_chars(): void
    {
        $this->getJson('/api/workshops/search?q=a')
             ->assertStatus(422);
    }

    public function test_workshop_search_excludes_disabled(): void
    {
        Workshop::factory()->create(['name' => 'ACTIVE ONE', 'status' => 'active']);
        Workshop::factory()->create(['name' => 'ACTIVE TWO', 'status' => 'disabled']);

        $this->getJson('/api/workshops/search?q=ACTIVE')
             ->assertOk()
             ->assertJsonCount(1);
    }

    // ── login ─────────────────────────────────────────────────────────────────

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['token', 'user' => ['id', 'email', 'status']]);
    }

    public function test_pending_user_can_login_and_gets_pending_status(): void
    {
        $user = User::factory()->pending()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
                 ->assertJsonPath('user.status', 'pending');
    }

    public function test_rejected_user_cannot_login(): void
    {
        $user = User::factory()->rejected()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->suspended()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    // ── rutas protegidas ──────────────────────────────────────────────────────

    public function test_me_returns_authenticated_user_with_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/me')
             ->assertOk()
             ->assertJson([
                 'id' => $user->id,
                 'email' => $user->email,
                 'status' => 'active',
             ]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_payload_includes_sidebar_summary_fields(): void
    {
        $principal = Workshop::factory()->create(['number' => 12, 'name' => 'La Fraternidad']);
        $adminWorkshop = Workshop::factory()->create(['number' => 7, 'name' => 'La Tolerancia']);

        $user = User::factory()->create([
            'last_name'  => 'Pérez',
            'masonic_id' => 'MAT-999',
            'status'     => 'active',
        ]);
        $user->workshopMemberships()->attach($principal->id, ['role' => 'member', 'status' => 'active', 'is_principal' => true]);
        $user->workshopMemberships()->attach($adminWorkshop->id, ['role' => 'admin', 'status' => 'active', 'is_principal' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('last_name', 'Pérez')
            ->assertJsonPath('masonic_id', 'MAT-999')
            ->assertJsonPath('principal_workshop.id', $principal->id)
            ->assertJsonPath('principal_workshop.number', 12)
            ->assertJsonPath('principal_workshop.name', 'La Fraternidad')
            ->assertJsonCount(1, 'admin_workshops')
            ->assertJsonPath('admin_workshops.0.number', 7)
            ->assertJsonPath('admin_workshops.0.name', 'La Tolerancia');
    }

    public function test_me_payload_has_null_principal_and_empty_admins_when_absent(): void
    {
        $user = User::factory()->create(['status' => 'active', 'masonic_id' => null, 'last_name' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('principal_workshop', null)
            ->assertJsonPath('masonic_id', null)
            ->assertJsonPath('admin_workshops', []);
    }

    // ── account status ───────────────────────────────────────────────────────

    public function test_account_status_returns_pending_info(): void
    {
        $user = User::factory()->pending()->unverified()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/account-status')
             ->assertOk()
             ->assertJson([
                 'status' => 'pending',
                 'email_verified' => false,
             ])
             ->assertJsonStructure(['verification_sent_at']);
    }

    public function test_account_status_returns_active_verified(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/account-status')
             ->assertOk()
             ->assertJson([
                 'status' => 'active',
                 'email_verified' => true,
             ]);
    }

    // ── resend verification ──────────────────────────────────────────────────

    public function test_resend_verification_sends_email(): void
    {
        Notification::fake();

        $user = User::factory()->pending()->unverified()->create();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/email/resend-verification')
             ->assertOk()
             ->assertJson(['message' => 'Email de verificación reenviado.']);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_verification_skips_if_already_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/email/resend-verification')
             ->assertOk()
             ->assertJson(['message' => 'El email ya fue verificado.']);

        Notification::assertNotSentTo($user, VerifyEmailNotification::class);
    }

    // ── email verify ─────────────────────────────────────────────────────────

    public function test_verify_email_marks_user_as_verified(): void
    {
        $user = User::factory()->pending()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->getJson($url)
             ->assertOk()
             ->assertJson(['message' => 'Email verificado correctamente.']);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_rejects_invalid_hash(): void
    {
        $user = User::factory()->pending()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalidhash'],
        );

        $this->getJson($url)->assertForbidden();
    }

    public function test_verify_email_rejects_expired_link(): void
    {
        $user = User::factory()->pending()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->subMinutes(1),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->getJson($url)->assertForbidden();
    }

    public function test_register_sends_verification_email(): void
    {
        Notification::fake();
        $workshop = Workshop::factory()->create();

        $this->postJson('/api/register', [
            'name'                  => 'Mail User',
            'email'                 => 'mail@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => $workshop->id,
        ])->assertStatus(201);

        $user = User::where('email', 'mail@test.com')->first();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verification_notification_contains_frontend_url(): void
    {
        Notification::fake();
        $workshop = Workshop::factory()->create();

        $this->postJson('/api/register', [
            'name'                  => 'Link User',
            'email'                 => 'link@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'workshop_id'           => $workshop->id,
        ])->assertStatus(201);

        $user = User::where('email', 'link@test.com')->first();

        Notification::assertSentTo($user, VerifyEmailNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $url = $mail->actionUrl;

            return str_contains($url, config('app.frontend_url') . '/verify-email');
        });
    }

    // ── logout ────────────────────────────────────────────────────────────────

    public function test_logout_invalidates_token(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/logout')
             ->assertOk()
             ->assertJson(['message' => 'Sesión cerrada.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
