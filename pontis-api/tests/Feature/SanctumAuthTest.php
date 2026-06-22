<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    // ── register ─────────────────────────────────────────────────────────────

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Juan Test',
            'email'                 => 'juan@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'juan@test.com']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'juan@test.com']);

        $this->postJson('/api/register', [
            'name'                  => 'Otro Juan',
            'email'                 => 'juan@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    // ── login ─────────────────────────────────────────────────────────────────

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['token', 'user' => ['id', 'email']]);
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

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/me')
             ->assertOk()
             ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
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

        // verificamos que el token fue eliminado de la BD
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
