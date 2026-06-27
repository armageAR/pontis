<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    private const WORKSHOP_NUMBERS = [469, 730, 1, 2];
    private const USERS_PER_WORKSHOP = 15;

    /**
     * Deterministic demo data — no faker dependency, so this seeder runs in
     * any environment (including production-mode Railway builds installed
     * with composer --no-dev).
     */
    private const FIRST_NAMES = [
        'Juan', 'Carlos', 'Roberto', 'Miguel', 'Jorge', 'Luis', 'Eduardo', 'Fernando',
        'Ricardo', 'Alberto', 'Daniel', 'Sergio', 'Gustavo', 'Pablo', 'Andrés',
        'Martín', 'Diego', 'Hernán', 'Marcelo', 'Raúl',
    ];

    private const LAST_NAMES = [
        'González', 'Rodríguez', 'Fernández', 'López', 'Martínez', 'Pérez', 'García',
        'Sánchez', 'Romero', 'Sosa', 'Torres', 'Álvarez', 'Ruiz', 'Ramírez', 'Flores',
        'Acosta', 'Benítez', 'Medina', 'Herrera', 'Aguirre',
    ];

    private const PROFESSIONS = [
        'Contador', 'Abogado', 'Médico', 'Ingeniero', 'Arquitecto', 'Electricista',
        'Carpintero', 'Docente', 'Comerciante', 'Programador', 'Plomero', 'Mecánico',
    ];

    public function run(): void
    {
        $workshops = Workshop::whereIn('number', self::WORKSHOP_NUMBERS)->get()->keyBy('number');

        $missing = collect(self::WORKSHOP_NUMBERS)->reject(fn ($n) => $workshops->has($n));
        if ($missing->isNotEmpty()) {
            $this->command->warn('Logias no encontradas con números: ' . $missing->join(', '));
        }

        $seq = 0;
        $created = 0;

        foreach ($workshops as $workshop) {
            $this->command->info("Creando usuarios para logia #{$workshop->number} — {$workshop->name}");

            for ($i = 0; $i < self::USERS_PER_WORKSHOP; $i++) {
                $name = self::FIRST_NAMES[$seq % count(self::FIRST_NAMES)];
                $lastName = self::LAST_NAMES[($seq * 7) % count(self::LAST_NAMES)];
                $profession = self::PROFESSIONS[$seq % count(self::PROFESSIONS)];

                $user = User::create([
                    'name'       => $name,
                    'last_name'  => $lastName,
                    'email'      => "demo{$workshop->number}_{$i}@pontis.test",
                    'password'   => 'password', // hashed by the model's 'hashed' cast
                    'role'       => 'user',
                    'status'     => 'active',
                    'profession' => $profession,
                ]);

                // email_verified_at is not mass-assignable; set it directly so the
                // demo users appear in searches and can authenticate.
                $user->email_verified_at = now();
                $user->save();

                // First user of each workshop is admin of the pivot relationship.
                $role = $i === 0 ? 'admin' : 'member';
                $workshop->users()->attach($user->id, ['role' => $role, 'status' => 'active']);

                $seq++;
                $created++;
            }
        }

        $this->command->info("Demo users seeded: {$created} usuarios en {$workshops->count()} logias.");
    }
}
