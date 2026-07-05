<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\UserPosition;
use App\Models\UserVisibilitySetting;
use App\Models\Workshop;
use Illuminate\Database\Seeder;

/**
 * Dataset determinístico de V1 (improve-test-seeders): 5 talleres reales, 8
 * usuarios activos por taller (6 Maestros, 1 Compañero, 1 Aprendiz), grados con
 * historial, cargos sólo para Maestros, dos Admin de Taller por taller y presets
 * de visibilidad que cubren incógnito, nada visible, todo visible y mixtos.
 *
 * No crea publicaciones (services/needs): diferidas a V2 (publication-v2-deferral).
 * Todos los usuarios usan password `password` y emails deterministas para QA.
 */
class DemoUsersSeeder extends Seeder
{
    /** Talleres demo (por número) y sus slug/provincia/localidad coherentes. */
    private const WORKSHOPS = [
        1    => ['slug' => 'uniondelplata',  'province' => 'Ciudad Autónoma de Buenos Aires', 'locality' => 'Ciudad Autónoma de Buenos Aires', 'neighborhood' => 'San Nicolás'],
        2    => ['slug' => 'confraternidad', 'province' => 'Ciudad Autónoma de Buenos Aires', 'locality' => 'Ciudad Autónoma de Buenos Aires', 'neighborhood' => 'Monserrat'],
        80   => ['slug' => 'laplata',        'province' => 'Buenos Aires',                    'locality' => 'La Plata',                        'neighborhood' => 'Centro'],
        702  => ['slug' => 'oconnor',        'province' => 'Salta',                           'locality' => 'Salta',                           'neighborhood' => 'Tres Cerritos'],
        1222 => ['slug' => 'luzdelara',      'province' => 'Chubut',                          'locality' => 'Rawson',                          'neighborhood' => 'Centro'],
    ];

    private const FIRST_NAMES = ['Juan', 'Carlos', 'Roberto', 'Miguel', 'Jorge', 'Luis', 'Eduardo', 'Fernando'];
    private const LAST_NAMES = ['González', 'Rodríguez', 'Fernández', 'López', 'Martínez', 'Pérez', 'García', 'Sánchez'];
    private const PROFESSIONS = ['Contador', 'Abogado', 'Médico clínico', 'Ingeniero civil', 'Arquitecto', 'Electricista matriculado', 'Docente universitario', 'Programador'];

    /**
     * Los 8 puestos del taller. `cargo` sólo para los 6 Maestros; `admin` marca
     * a los dos Admin de Taller (Venerable Maestro y Primer Vigilante).
     */
    private const SLOTS = [
        ['key' => 'maestro1', 'degree' => 'maestro',   'cargo' => 'Venerable Maestro',     'admin' => true,  'preset' => 'open'],
        ['key' => 'maestro2', 'degree' => 'maestro',   'cargo' => 'Primer Vigilante',      'admin' => true,  'preset' => 'professional_visible'],
        ['key' => 'maestro3', 'degree' => 'maestro',   'cargo' => 'Segundo Vigilante',     'admin' => false, 'preset' => 'incognito'],
        ['key' => 'maestro4', 'degree' => 'maestro',   'cargo' => 'Maestro de Ceremonias', 'admin' => false, 'preset' => 'private'],
        ['key' => 'maestro5', 'degree' => 'maestro',   'cargo' => 'Experto',               'admin' => false, 'preset' => 'workshop_only'],
        ['key' => 'maestro6', 'degree' => 'maestro',   'cargo' => 'Tesorero',              'admin' => false, 'preset' => 'private_contact'],
        ['key' => 'companero', 'degree' => 'companero', 'cargo' => null,                    'admin' => false, 'preset' => 'all_my_workshops'],
        ['key' => 'aprendiz',  'degree' => 'aprendiz',  'cargo' => null,                    'admin' => false, 'preset' => 'balanced_mixed'],
    ];

    /**
     * Presets de visibilidad. Niveles válidos por bloque: private, workshop,
     * my_workshops, registered. La identidad incógnita usa anonymous_search.
     */
    private const PRESETS = [
        // Muestra todo a registrados.
        'open' => [
            'identity' => ['registered', false], 'masonic' => ['registered', false], 'contact' => ['registered', false],
            'location' => ['registered', false], 'profession' => ['registered', false], 'bio' => ['registered', false],
            'degrees' => ['registered', false], 'positions' => ['registered', false],
        ],
        // No muestra nada.
        'private' => [
            'identity' => ['private', false], 'masonic' => ['private', false], 'contact' => ['private', false],
            'location' => ['private', false], 'profession' => ['private', false], 'bio' => ['private', false],
            'degrees' => ['private', false], 'positions' => ['private', false],
        ],
        // Incógnito: aparece en búsquedas con identidad reservada.
        'incognito' => [
            'identity' => ['workshop', true], 'masonic' => ['private', false], 'contact' => ['private', false],
            'location' => ['registered', false], 'profession' => ['registered', false], 'bio' => ['private', false],
            'degrees' => ['private', false], 'positions' => ['private', false],
        ],
        // Mixtos.
        'professional_visible' => [
            'identity' => ['my_workshops', false], 'masonic' => ['workshop', false], 'contact' => ['my_workshops', false],
            'location' => ['registered', false], 'profession' => ['registered', false], 'bio' => ['registered', false],
            'degrees' => ['workshop', false], 'positions' => ['registered', false],
        ],
        'workshop_only' => [
            'identity' => ['workshop', false], 'masonic' => ['workshop', false], 'contact' => ['workshop', false],
            'location' => ['my_workshops', false], 'profession' => ['registered', false], 'bio' => ['my_workshops', false],
            'degrees' => ['workshop', false], 'positions' => ['workshop', false],
        ],
        'private_contact' => [
            'identity' => ['registered', false], 'masonic' => ['my_workshops', false], 'contact' => ['private', false],
            'location' => ['workshop', false], 'profession' => ['registered', false], 'bio' => ['registered', false],
            'degrees' => ['my_workshops', false], 'positions' => ['workshop', false],
        ],
        'all_my_workshops' => [
            'identity' => ['my_workshops', false], 'masonic' => ['my_workshops', false], 'contact' => ['my_workshops', false],
            'location' => ['registered', false], 'profession' => ['registered', false], 'bio' => ['my_workshops', false],
            'degrees' => ['my_workshops', false], 'positions' => ['my_workshops', false],
        ],
        'balanced_mixed' => [
            'identity' => ['registered', false], 'masonic' => ['workshop', false], 'contact' => ['my_workshops', false],
            'location' => ['registered', false], 'profession' => ['registered', false], 'bio' => ['workshop', false],
            'degrees' => ['workshop', false], 'positions' => ['my_workshops', false],
        ],
    ];

    public function run(): void
    {
        $positions = Position::whereIn('name', array_filter(array_column(self::SLOTS, 'cargo')))
            ->get()->keyBy('name');

        $created = 0;

        foreach (self::WORKSHOPS as $number => $meta) {
            $workshop = Workshop::where('number', $number)->first();
            if (! $workshop) {
                $this->command?->warn("Taller #{$number} no encontrado; corré WorkshopSeeder primero.");
                continue;
            }

            foreach (self::SLOTS as $i => $slot) {
                $user = $this->seedUser($number, $meta, $slot, $i);
                $this->resetDemoRelations($user);
                $this->seedMembership($user, $workshop, $slot['admin']);
                $this->seedDegrees($user, $workshop, $slot['degree']);
                $this->seedPosition($user, $workshop, $slot, $positions);
                $this->seedVisibility($user, self::PRESETS[$slot['preset']]);
                $created++;
            }
        }

        $this->command?->info("Demo users seeded: {$created} usuarios activos en ".count(self::WORKSHOPS).' talleres.');
    }

    private function seedUser(int $number, array $meta, array $slot, int $i): User
    {
        $email = "{$meta['slug']}.{$slot['key']}@pontis.test";
        $name = self::FIRST_NAMES[$i];
        $lastName = self::LAST_NAMES[$i];
        $profession = self::PROFESSIONS[$i];

        return User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'last_name' => $lastName,
            'password' => 'password',
            'role' => 'user',
            'status' => 'active',
            'email_verified_at' => now(),
            'dni' => str_pad((string) ($number * 100 + $i + 1), 8, '0', STR_PAD_LEFT),
            'masonic_id' => 'PON-'.$number.'-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
            'birth_date' => (1965 + $i).'-0'.(($i % 9) + 1).'-15',
            'initiation_date' => (2005 + $i).'-0'.(($i % 9) + 1).'-03',
            'masonic_status' => 'active',
            'phone' => '+54 9 11 '.str_pad((string) ($number * 1000 + $i), 8, '0', STR_PAD_LEFT),
            'country' => 'Argentina',
            'province' => $meta['province'],
            'locality' => $meta['locality'],
            'neighborhood' => $meta['neighborhood'],
            'address' => 'Calle Demo '.(100 + $i),
            'profession' => $profession,
            'occupation' => $profession,
            'bio' => "Perfil demo de {$meta['locality']} para probar visibilidad y búsquedas.",
        ]);
    }

    private function resetDemoRelations(User $user): void
    {
        $user->workshopMemberships()->detach();
        UserVisibilitySetting::where('user_id', $user->id)->delete();
        UserDegree::where('user_id', $user->id)->delete();
        UserPosition::where('user_id', $user->id)->delete();
    }

    private function seedMembership(User $user, Workshop $workshop, bool $admin): void
    {
        $user->workshopMemberships()->attach($workshop->id, [
            'role' => $admin ? 'admin' : 'member',
            'status' => 'active',
            'is_principal' => true,
            'requested_by_user' => false,
        ]);
    }

    /** Historial de grados según progresión: aprendiz → compañero → maestro. */
    private function seedDegrees(User $user, Workshop $workshop, string $degree): void
    {
        $rows = match ($degree) {
            'maestro' => [
                ['aprendiz',  '2012-03-10', '2015-06-14'],
                ['companero', '2015-06-15', '2018-09-19'],
                ['maestro',   '2018-09-20', null],
            ],
            'companero' => [
                ['aprendiz',  '2018-03-10', '2021-06-14'],
                ['companero', '2021-06-15', null],
            ],
            default => [
                ['aprendiz', '2022-03-10', null],
            ],
        };

        foreach ($rows as [$deg, $start, $end]) {
            UserDegree::create([
                'user_id' => $user->id,
                'workshop_id' => $workshop->id,
                'degree' => $deg,
                'start_date' => $start,
                'end_date' => $end,
                'validation_status' => 'validated',
                'validated_at' => now(),
                'notes' => 'Grado demo.',
            ]);
        }
    }

    private function seedPosition(User $user, Workshop $workshop, array $slot, $positions): void
    {
        if ($slot['cargo'] === null) {
            return; // Compañero y Aprendiz no tienen cargo.
        }
        $position = $positions->get($slot['cargo']);
        if (! $position) {
            $this->command?->warn("Cargo '{$slot['cargo']}' no existe; corré PositionSeeder primero.");
            return;
        }

        UserPosition::create([
            'user_id' => $user->id,
            'position_id' => $position->id,
            'workshop_id' => $workshop->id,
            'start_date' => '2022-01-10',
            'end_date' => null,
            'validation_status' => 'validated',
            'validated_at' => now(),
            'notes' => 'Cargo demo vigente.',
        ]);
    }

    private function seedVisibility(User $user, array $preset): void
    {
        foreach ($preset as $block => [$visibility, $anonymousSearch]) {
            UserVisibilitySetting::create([
                'user_id' => $user->id,
                'block' => $block,
                'visibility' => $visibility,
                'anonymous_search' => $anonymousSearch,
            ]);
        }
    }
}
