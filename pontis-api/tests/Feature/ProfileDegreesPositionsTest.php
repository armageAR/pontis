<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDegreesPositionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithWorkshop(): array
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $workshop = Workshop::factory()->create();
        $user->workshopMemberships()->attach($workshop->id, ['role' => 'member', 'status' => 'active', 'is_principal' => true]);
        return [$user, $workshop];
    }

    // ── 4.1 Foreign workshop rejected ────────────────────────────────────────

    public function test_degree_with_foreign_workshop_is_rejected(): void
    {
        [$user] = $this->userWithWorkshop();
        $foreign = Workshop::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/degrees', [
                'degree'      => 'aprendiz',
                'workshop_id' => $foreign->id,
                'start_date'  => '2020-01-01',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('user_degrees', 0);
    }

    public function test_position_with_foreign_workshop_is_rejected(): void
    {
        [$user] = $this->userWithWorkshop();
        $foreign = Workshop::factory()->create();
        $position = Position::create(['name' => 'Secretario']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/positions', [
                'position_id' => $position->id,
                'workshop_id' => $foreign->id,
                'start_date'  => '2020-01-01',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('user_positions', 0);
    }

    public function test_position_with_own_workshop_is_accepted(): void
    {
        [$user, $workshop] = $this->userWithWorkshop();
        $position = Position::create(['name' => 'Secretario']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/positions', [
                'position_id' => $position->id,
                'workshop_id' => $workshop->id,
                'start_date'  => '2020-01-01',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('user_positions', [
            'user_id'     => $user->id,
            'workshop_id' => $workshop->id,
        ]);
    }

    // ── 4.2 Valid progression ────────────────────────────────────────────────

    public function test_valid_degree_progression_aprendiz_companero_maestro(): void
    {
        [$user] = $this->userWithWorkshop();
        $acting = $this->actingAs($user, 'sanctum');

        $acting->postJson('/api/profile/degrees', ['degree' => 'aprendiz', 'start_date' => '2020-01-01'])->assertCreated();
        $acting->postJson('/api/profile/degrees', ['degree' => 'companero', 'start_date' => '2021-01-01'])->assertCreated();
        $acting->postJson('/api/profile/degrees', ['degree' => 'maestro', 'start_date' => '2022-01-01'])->assertCreated();

        $this->assertSame(3, UserDegree::where('user_id', $user->id)->count());
    }

    // ── 4.3 Invalid progression rejected ─────────────────────────────────────

    public function test_first_degree_must_be_aprendiz(): void
    {
        [$user] = $this->userWithWorkshop();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/degrees', ['degree' => 'companero', 'start_date' => '2020-01-01'])
            ->assertStatus(422);

        $this->assertDatabaseCount('user_degrees', 0);
    }

    public function test_degree_skip_is_rejected(): void
    {
        [$user] = $this->userWithWorkshop();
        UserDegree::create(['user_id' => $user->id, 'degree' => 'aprendiz', 'start_date' => '2020-01-01']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/degrees', ['degree' => 'maestro', 'start_date' => '2021-01-01'])
            ->assertStatus(422);

        $this->assertSame('aprendiz', UserDegree::where('user_id', $user->id)->orderByDesc('start_date')->first()->degree);
    }

    public function test_duplicate_current_degree_is_rejected(): void
    {
        [$user] = $this->userWithWorkshop();
        UserDegree::create(['user_id' => $user->id, 'degree' => 'aprendiz', 'start_date' => '2020-01-01']);
        UserDegree::create(['user_id' => $user->id, 'degree' => 'companero', 'start_date' => '2021-01-01']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/degrees', ['degree' => 'companero', 'start_date' => '2022-01-01'])
            ->assertStatus(422);

        $this->assertSame(2, UserDegree::where('user_id', $user->id)->count());
    }

    public function test_degree_regression_is_rejected(): void
    {
        [$user] = $this->userWithWorkshop();
        UserDegree::create(['user_id' => $user->id, 'degree' => 'aprendiz', 'start_date' => '2020-01-01']);
        UserDegree::create(['user_id' => $user->id, 'degree' => 'companero', 'start_date' => '2021-01-01']);
        UserDegree::create(['user_id' => $user->id, 'degree' => 'maestro', 'start_date' => '2022-01-01']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile/degrees', ['degree' => 'companero', 'start_date' => '2023-01-01'])
            ->assertStatus(422);

        $this->assertSame(3, UserDegree::where('user_id', $user->id)->count());
    }

    // ── 4.4 History preserved, no manual end-date gaps ───────────────────────

    public function test_degree_end_date_is_derived_and_history_preserved(): void
    {
        [$user] = $this->userWithWorkshop();
        $acting = $this->actingAs($user, 'sanctum');

        // Enviar end_date manual: debe ignorarse (no hay fecha de fin manual en grados).
        $acting->postJson('/api/profile/degrees', ['degree' => 'aprendiz', 'start_date' => '2020-01-01', 'end_date' => '2020-06-01'])->assertCreated();
        $acting->postJson('/api/profile/degrees', ['degree' => 'companero', 'start_date' => '2021-01-01'])->assertCreated();

        // Historial completo preservado.
        $this->assertSame(2, UserDegree::where('user_id', $user->id)->count());

        $degrees = $acting->getJson('/api/profile/degrees')->assertOk()->json();
        $byDegree = collect($degrees)->keyBy('degree');

        // El fin del período de aprendiz se deriva del inicio de companero.
        $this->assertStringStartsWith('2021-01-01', $byDegree['aprendiz']['end_date']);
        // El grado vigente (companero) no tiene fin.
        $this->assertNull($byDegree['companero']['end_date']);
    }
}
