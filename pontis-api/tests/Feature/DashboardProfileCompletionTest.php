<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDegree;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_profile_completion_as_percent_0_to_100(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $completion = $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->json('profile_completion');

        $this->assertIsInt($completion['percent']);
        $this->assertGreaterThanOrEqual(0, $completion['percent']);
        $this->assertLessThanOrEqual(100, $completion['percent']);
        $this->assertSame($completion['total'], 16);
        $this->assertLessThanOrEqual($completion['total'], $completion['completed']);
    }

    public function test_fully_completed_profile_reports_100(): void
    {
        $workshop = Workshop::factory()->create();
        $user = User::factory()->create([
            'role' => 'user', 'status' => 'active',
            'name' => 'Juan', 'last_name' => 'Pérez', 'dni' => '12345678', 'masonic_id' => 'M-1',
            'birth_date' => '1980-01-01', 'initiation_date' => '2010-01-01', 'masonic_status' => 'active',
            'phone' => '+54 11 1234', 'province' => 'Salta', 'locality' => 'Salta',
            'profession' => 'Contador', 'occupation' => 'Contador', 'bio' => 'Bio demo.',
        ]);
        $user->workshopMemberships()->attach($workshop->id, ['role' => 'member', 'status' => 'active', 'is_principal' => true]);
        UserDegree::create([
            'user_id' => $user->id, 'workshop_id' => $workshop->id, 'degree' => 'maestro',
            'start_date' => '2015-01-01', 'validation_status' => 'validated', 'validated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('profile_completion.percent', 100)
            ->assertJsonPath('profile_completion.completed', 16);
    }

    public function test_existing_dashboard_fields_are_preserved(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'membership_notifications',
                'is_workshop_admin',
                'pending_validation_count',
                'profile_completion' => ['percent', 'completed', 'total'],
            ]);
    }
}
