<?php

namespace Tests\Feature;

use App\Models\Need;
use App\Models\Service;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\UserPosition;
use App\Models\Workshop;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Database\Seeders\WorkshopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private const WORKSHOP_NUMBERS = [1, 2, 80, 702, 1222];

    private function seedDemo(): void
    {
        $this->seed(PositionSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(WorkshopSeeder::class);
        $this->seed(DemoUsersSeeder::class);
    }

    public function test_superadmin_login_credentials(): void
    {
        $this->seed(SuperAdminSeeder::class);
        $admin = User::where('email', 'admin@pontis.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('superadmin', $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_seeds_exactly_five_workshops_and_forty_active_users(): void
    {
        $this->seedDemo();

        foreach (self::WORKSHOP_NUMBERS as $number) {
            $this->assertDatabaseHas('workshops', ['number' => $number]);
        }

        // 40 usuarios demo activos (sin contar el superadmin).
        $demo = User::where('email', 'like', '%@pontis.test')->get();
        $this->assertCount(40, $demo);
        $this->assertTrue($demo->every(fn ($u) => $u->status->value === 'active'));
        $this->assertTrue($demo->every(fn ($u) => Hash::check('password', $u->password)));
    }

    public function test_per_workshop_degree_distribution(): void
    {
        $this->seedDemo();

        foreach (self::WORKSHOP_NUMBERS as $number) {
            $workshop = Workshop::where('number', $number)->first();
            $userIds = $workshop->users()->pluck('users.id');

            // Grado vigente = grado sin end_date.
            $current = UserDegree::whereIn('user_id', $userIds)->whereNull('end_date')->get()
                ->groupBy('degree')->map->count();

            $this->assertSame(6, $current['maestro'] ?? 0, "Taller {$number}: maestros");
            $this->assertSame(1, $current['companero'] ?? 0, "Taller {$number}: compañeros");
            $this->assertSame(1, $current['aprendiz'] ?? 0, "Taller {$number}: aprendices");
        }
    }

    public function test_maestros_have_full_degree_history_and_positions(): void
    {
        $this->seedDemo();

        $workshop = Workshop::where('number', 1)->first();
        $maestro = User::where('email', 'uniondelplata.maestro1@pontis.test')->first();

        // Historial completo: aprendiz, compañero, maestro.
        $degrees = UserDegree::where('user_id', $maestro->id)->pluck('degree')->sort()->values()->all();
        $this->assertSame(['aprendiz', 'companero', 'maestro'], $degrees);

        // Cargo vigente.
        $this->assertDatabaseHas('user_positions', ['user_id' => $maestro->id, 'workshop_id' => $workshop->id]);

        // El aprendiz no tiene cargo.
        $aprendiz = User::where('email', 'uniondelplata.aprendiz@pontis.test')->first();
        $this->assertSame(0, UserPosition::where('user_id', $aprendiz->id)->count());
    }

    public function test_two_workshop_admins_per_workshop(): void
    {
        $this->seedDemo();

        foreach (self::WORKSHOP_NUMBERS as $number) {
            $workshop = Workshop::where('number', $number)->first();
            $admins = $workshop->users()->wherePivot('role', 'admin')->count();
            $this->assertSame(2, $admins, "Taller {$number}: admins de taller");
        }
    }

    public function test_visibility_coverage_per_workshop(): void
    {
        $this->seedDemo();

        foreach (self::WORKSHOP_NUMBERS as $number) {
            $workshop = Workshop::where('number', $number)->first();
            $userIds = $workshop->users()->pluck('users.id');

            $identity = \DB::table('user_visibility_settings')
                ->whereIn('user_id', $userIds)->where('block', 'identity')->get();

            // Al menos un incógnito (anonymous_search), uno que no muestra nada
            // (identidad private) y uno que muestra todo (identidad registered).
            $this->assertTrue($identity->contains(fn ($s) => (bool) $s->anonymous_search === true), "Taller {$number}: incógnito");
            $this->assertTrue($identity->contains(fn ($s) => $s->visibility === 'private' && ! $s->anonymous_search), "Taller {$number}: nada visible");
            $this->assertTrue($identity->contains(fn ($s) => $s->visibility === 'registered'), "Taller {$number}: todo visible");
        }
    }

    public function test_no_publications_are_created(): void
    {
        $this->seedDemo();

        $this->assertSame(0, Service::count());
        $this->assertSame(0, Need::count());
    }

    public function test_seeding_twice_keeps_counts_stable(): void
    {
        $this->seedDemo();
        $this->seedDemo();

        $this->assertCount(40, User::where('email', 'like', '%@pontis.test')->get());
        $this->assertSame(5, Workshop::whereIn('number', self::WORKSHOP_NUMBERS)->count());
        // Cada usuario mantiene exactamente una membresía activa.
        $this->assertSame(40, \DB::table('user_workshop')->count());
    }
}
