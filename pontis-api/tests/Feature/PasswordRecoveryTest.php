<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserStatus::ACTIVE,
            'password' => 'old-password',
        ], $overrides));
    }

    private function resetTokenFor(User $user): string
    {
        $token = '';
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });
        return $token;
    }

    public function test_forgot_password_returns_neutral_response(): void
    {
        Notification::fake();
        $user = $this->activeUser(['email' => 'hermano@example.com']);

        $known = $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->json('message');

        $unknown = $this->postJson('/api/forgot-password', ['email' => 'nadie@example.com'])
            ->assertOk()
            ->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_can_reset_password_once_and_active_tokens_are_revoked(): void
    {
        Notification::fake();
        $user = $this->activeUser(['email' => 'hermano@example.com']);
        $user->createToken('api');

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $token = $this->resetTokenFor($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ])->assertStatus(422);
    }

    public function test_expired_token_is_rejected(): void
    {
        Notification::fake();
        $user = $this->activeUser(['email' => 'hermano@example.com']);

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $token = $this->resetTokenFor($user);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(422);
    }

    public function test_suspended_user_can_reset_password_but_login_remains_blocked(): void
    {
        Notification::fake();
        $user = $this->activeUser([
            'email' => 'suspendido@example.com',
            'status' => UserStatus::SUSPENDED,
        ]);

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $token = $this->resetTokenFor($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertSame(UserStatus::SUSPENDED, $user->fresh()->status);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertStatus(422);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/forgot-password', ['email' => "rate{$i}@example.com"])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => 'rate-limit@example.com'])->assertStatus(429);
    }
}
