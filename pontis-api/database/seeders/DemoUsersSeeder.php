<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Need;
use App\Models\Position;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\UserDegree;
use App\Models\UserPosition;
use App\Models\UserVisibilitySetting;
use App\Models\Workshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoUsersSeeder extends Seeder
{
    private const PREFERRED_WORKSHOP_NUMBERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 50, 100, 200, 300, 387, 469, 730];
    private const MAX_WORKSHOPS = 12;
    private const USERS_PER_WORKSHOP = 18;

    private const FIRST_NAMES = [
        'Juan', 'Carlos', 'Roberto', 'Miguel', 'Jorge', 'Luis', 'Eduardo', 'Fernando',
        'Ricardo', 'Alberto', 'Daniel', 'Sergio', 'Gustavo', 'Pablo', 'Andrés',
        'Martín', 'Diego', 'Hernán', 'Marcelo', 'Raúl', 'Guillermo', 'Tomás',
        'Nicolás', 'Esteban', 'Federico', 'Claudio', 'Ignacio', 'Patricio',
        'Alejandro', 'Damián', 'Leandro', 'Mauricio', 'Sebastián', 'Víctor',
    ];

    private const LAST_NAMES = [
        'González', 'Rodríguez', 'Fernández', 'López', 'Martínez', 'Pérez', 'García',
        'Sánchez', 'Romero', 'Sosa', 'Torres', 'Álvarez', 'Ruiz', 'Ramírez', 'Flores',
        'Acosta', 'Benítez', 'Medina', 'Herrera', 'Aguirre', 'Castro', 'Silva',
        'Molina', 'Vega', 'Rojas', 'Méndez', 'Cabrera', 'Peralta', 'Navarro',
        'Ibarra', 'Suárez', 'Domínguez', 'Campos', 'Figueroa',
    ];

    private const PROFESSIONS = [
        'Contador', 'Abogado', 'Médico clínico', 'Ingeniero civil', 'Arquitecto',
        'Electricista matriculado', 'Carpintero', 'Docente universitario',
        'Comerciante', 'Programador', 'Plomero', 'Mecánico automotor',
        'Psicólogo', 'Diseñador gráfico', 'Martillero público',
        'Kinesiólogo', 'Odontólogo', 'Analista de datos', 'Técnico en seguridad',
        'Consultor de recursos humanos', 'Corredor inmobiliario', 'Chef',
        'Traductor', 'Periodista', 'Veterinario', 'Agrimensor', 'Farmacéutico',
    ];

    private const LOCATIONS = [
        ['province' => 'Ciudad Autónoma de Buenos Aires', 'locality' => 'Buenos Aires', 'neighborhood' => 'San Nicolás'],
        ['province' => 'Buenos Aires', 'locality' => 'La Plata', 'neighborhood' => 'Centro'],
        ['province' => 'Buenos Aires', 'locality' => 'Mar del Plata', 'neighborhood' => 'Güemes'],
        ['province' => 'Córdoba', 'locality' => 'Córdoba', 'neighborhood' => 'Nueva Córdoba'],
        ['province' => 'Córdoba', 'locality' => 'Río Cuarto', 'neighborhood' => 'Centro'],
        ['province' => 'Santa Fe', 'locality' => 'Rosario', 'neighborhood' => 'Pichincha'],
        ['province' => 'Santa Fe', 'locality' => 'Santa Fe', 'neighborhood' => 'Candioti'],
        ['province' => 'Mendoza', 'locality' => 'Mendoza', 'neighborhood' => 'Quinta Sección'],
        ['province' => 'Tucumán', 'locality' => 'San Miguel de Tucumán', 'neighborhood' => 'Barrio Norte'],
        ['province' => 'Salta', 'locality' => 'Salta', 'neighborhood' => 'Tres Cerritos'],
        ['province' => 'Neuquén', 'locality' => 'Neuquén', 'neighborhood' => 'Área Centro Este'],
        ['province' => 'Río Negro', 'locality' => 'Bariloche', 'neighborhood' => 'Centro'],
        ['province' => 'Entre Ríos', 'locality' => 'Paraná', 'neighborhood' => 'Centro'],
        ['province' => 'Chubut', 'locality' => 'Puerto Madryn', 'neighborhood' => 'Sur'],
        ['province' => 'Misiones', 'locality' => 'Posadas', 'neighborhood' => 'Villa Sarita'],
        ['province' => 'San Juan', 'locality' => 'San Juan', 'neighborhood' => 'Concepción'],
        ['province' => 'Corrientes', 'locality' => 'Corrientes', 'neighborhood' => 'Centro'],
        ['province' => 'Jujuy', 'locality' => 'San Salvador de Jujuy', 'neighborhood' => 'Ciudad de Nieva'],
        ['province' => 'La Pampa', 'locality' => 'Santa Rosa', 'neighborhood' => 'Villa Alonso'],
        ['province' => 'San Luis', 'locality' => 'San Luis', 'neighborhood' => 'Centro'],
        ['province' => 'Tierra del Fuego', 'locality' => 'Ushuaia', 'neighborhood' => 'Centro'],
        ['province' => 'Santa Cruz', 'locality' => 'Río Gallegos', 'neighborhood' => 'Centro'],
        ['province' => 'Chaco', 'locality' => 'Resistencia', 'neighborhood' => 'Villa San Martín'],
        ['province' => 'Catamarca', 'locality' => 'San Fernando del Valle de Catamarca', 'neighborhood' => 'Centro'],
    ];

    private const ACCOUNT_STATUSES = [
        UserStatus::ACTIVE->value,
        UserStatus::PENDING->value,
        UserStatus::VERIFYING->value,
        UserStatus::REJECTED->value,
        UserStatus::SUSPENDED->value,
        UserStatus::INACTIVE->value,
    ];

    private const MEMBERSHIP_STATUSES = [
        'active',
        'pending',
        'correction_requested',
        'rejected',
        'inactive',
        'suspended',
        'ended',
        'historical',
    ];

    private const VISIBILITY_PRESETS = [
        'open_profile' => [
            'identity' => 'registered', 'masonic' => 'registered', 'contact' => 'registered',
            'location' => 'registered', 'profession' => 'registered', 'bio' => 'registered',
            'degrees' => 'registered', 'positions' => 'registered',
        ],
        'workshop_only' => [
            'identity' => 'workshop', 'masonic' => 'workshop', 'contact' => 'workshop',
            'location' => 'my_workshops', 'profession' => 'registered', 'bio' => 'my_workshops',
            'degrees' => 'workshop', 'positions' => 'workshop',
        ],
        'private_contact' => [
            'identity' => 'registered', 'masonic' => 'my_workshops', 'contact' => 'private',
            'location' => 'workshop', 'profession' => 'registered', 'bio' => 'registered',
            'degrees' => 'my_workshops', 'positions' => 'workshop',
        ],
        'anonymous_publications' => [
            'identity' => 'private', 'masonic' => 'private', 'contact' => 'private',
            'location' => 'registered', 'profession' => 'anonymous', 'bio' => 'anonymous',
            'degrees' => 'private', 'positions' => 'private',
        ],
        'all_my_workshops' => [
            'identity' => 'my_workshops', 'masonic' => 'my_workshops',
            'contact' => 'my_workshops', 'location' => 'registered', 'profession' => 'registered',
            'bio' => 'my_workshops', 'degrees' => 'my_workshops', 'positions' => 'my_workshops',
        ],
        'strict_private' => [
            'identity' => 'private', 'masonic' => 'private', 'contact' => 'private',
            'location' => 'private', 'profession' => 'workshop', 'bio' => 'private',
            'degrees' => 'private', 'positions' => 'private',
        ],
        'professional_visible' => [
            'identity' => 'my_workshops', 'masonic' => 'workshop', 'contact' => 'my_workshops',
            'location' => 'registered', 'profession' => 'registered', 'bio' => 'registered',
            'degrees' => 'workshop', 'positions' => 'registered',
        ],
    ];

    public function run(): void
    {
        $workshops = $this->resolveDemoWorkshops();

        if ($workshops->isEmpty()) {
            $this->command->warn('No hay logias para asociar usuarios demo.');
            return;
        }

        $this->command->info('Logias demo seleccionadas: '.$workshops->pluck('number')->join(', '));

        $categories = ServiceCategory::orderBy('name')->get()->values();
        $positions = Position::orderBy('name')->get()->values();
        $admin = User::where('role', 'superadmin')->first();

        $seq = 0;
        $seeded = 0;

        foreach ($workshops as $workshop) {
            $this->command->info("Generando usuarios demo para logia #{$workshop->number} - {$workshop->name}");

            for ($index = 0; $index < self::USERS_PER_WORKSHOP; $index++) {
                $scenario = $this->scenarioFor($seq, $index);
                $location = self::LOCATIONS[$seq % count(self::LOCATIONS)];
                $name = self::FIRST_NAMES[$seq % count(self::FIRST_NAMES)];
                $lastName = self::LAST_NAMES[($seq * 7) % count(self::LAST_NAMES)];
                $profession = self::PROFESSIONS[$seq % count(self::PROFESSIONS)];
                $email = "demo{$workshop->number}_{$index}@pontis.test";

                $user = User::updateOrCreate(
                    ['email' => $email],
                    $this->userPayload($seq, $name, $lastName, $profession, $location, $scenario)
                );

                $user->email_verified_at = $scenario['verified'] ? Carbon::now()->subDays($seq % 45) : null;
                $user->save();

                $this->resetDemoRelations($user);
                $this->seedMemberships($user, $workshop, $workshops->values(), $scenario, $index, $seq);
                $this->seedVisibility($user, $scenario['visibility_preset']);
                $this->seedDegrees($user, $workshop, $scenario);
                $this->seedPositions($user, $workshop, $positions, $scenario, $seq);
                $this->seedPublications($user, $categories, $admin, $scenario, $location, $seq);

                $seq++;
                $seeded++;
            }
        }

        $this->command->info("Demo users seeded: {$seeded} usuarios con estados, perfiles, seguridad y publicaciones variadas.");
    }

    private function resolveDemoWorkshops()
    {
        $preferred = Workshop::whereIn('number', self::PREFERRED_WORKSHOP_NUMBERS)
            ->orderByRaw('CASE number '.collect(self::PREFERRED_WORKSHOP_NUMBERS)->map(fn ($number, $index) => "WHEN {$number} THEN {$index}")->join(' ').' END')
            ->get();

        $selected = $preferred;

        if ($selected->count() < self::MAX_WORKSHOPS) {
            $extra = Workshop::whereNotIn('id', $selected->pluck('id'))
                ->where('status', 'active')
                ->orderBy('number')
                ->take(self::MAX_WORKSHOPS - $selected->count())
                ->get();

            $selected = $selected->concat($extra);
        }

        if ($selected->isEmpty()) {
            $selected = Workshop::orderBy('number')->take(self::MAX_WORKSHOPS)->get();
        }

        return $selected->take(self::MAX_WORKSHOPS)->values();
    }

    private function scenarioFor(int $seq, int $index): array
    {
        $isWorkshopAdmin = $index === 0;

        return [
            'account_status' => self::ACCOUNT_STATUSES[$seq % count(self::ACCOUNT_STATUSES)],
            'membership_status' => $isWorkshopAdmin ? 'active' : self::MEMBERSHIP_STATUSES[$seq % count(self::MEMBERSHIP_STATUSES)],
            'verified' => $seq % 6 !== 2,
            'masonic_status' => ['active', 'inactive', 'suspended', 'discharged'][$seq % 4],
            'degree' => ['maestro', 'companero', 'aprendiz'][$seq % 3],
            'role' => $isWorkshopAdmin ? 'admin' : 'member',
            'second_workshop' => $seq % 5 === 0,
            'visibility_preset' => array_keys(self::VISIBILITY_PRESETS)[$seq % count(self::VISIBILITY_PRESETS)],
            'services' => $this->serviceScenarios($seq),
            'needs' => $this->needScenarios($seq),
        ];
    }

    private function userPayload(
        int $seq,
        string $name,
        string $lastName,
        string $profession,
        array $location,
        array $scenario
    ): array {
        $birthYear = 1968 + ($seq % 28);
        $initiationYear = 2005 + ($seq % 18);
        $slug = strtolower(str_replace(' ', '.', "{$name}.{$lastName}"));

        return [
            'name' => $name,
            'last_name' => $lastName,
            'password' => 'password',
            'role' => 'user',
            'status' => $scenario['account_status'],
            'dni' => str_pad((string) (23000000 + ($seq * 137)), 8, '0', STR_PAD_LEFT),
            'masonic_id' => 'PON-'.str_pad((string) ($seq + 1001), 5, '0', STR_PAD_LEFT),
            'birth_date' => "{$birthYear}-".str_pad((string) (($seq % 12) + 1), 2, '0', STR_PAD_LEFT).'-15',
            'initiation_date' => "{$initiationYear}-".str_pad((string) (($seq % 12) + 1), 2, '0', STR_PAD_LEFT).'-03',
            'masonic_status' => $scenario['masonic_status'],
            'phone' => '+54 9 11 '.str_pad((string) (40000000 + $seq), 8, '0', STR_PAD_LEFT),
            'phone_fixed' => '+54 11 '.str_pad((string) (43000000 + $seq), 8, '0', STR_PAD_LEFT),
            'whatsapp' => '+54 9 11 '.str_pad((string) (50000000 + $seq), 8, '0', STR_PAD_LEFT),
            'alternative_email' => "contacto.{$seq}@pontis.test",
            'contact_preference' => ['email', 'whatsapp', 'phone'][$seq % 3],
            'country' => 'Argentina',
            'province' => $location['province'],
            'locality' => $location['locality'],
            'neighborhood' => $location['neighborhood'],
            'address' => 'Calle Demo '.(100 + $seq),
            'profession' => $profession,
            'occupation' => $profession,
            'company' => ['Independiente', 'Estudio propio', 'Hospital regional', 'PyME familiar'][$seq % 4],
            'profession_description' => "Experiencia comprobable en {$profession} y acompañamiento a hermanos.",
            'secondary_activities' => ['Mentoría', 'Capacitaciones', 'Gestión de proyectos', 'Voluntariado'][$seq % 4],
            'knowledge_areas' => ['Administración, procesos', 'Salud, bienestar', 'Tecnología, datos', 'Construcción, oficios'][$seq % 4],
            'certifications' => ['Matrícula vigente', 'Diplomatura profesional', 'Certificación técnica', 'Formación continua'][$seq % 4],
            'bio' => "Perfil demo para probar visibilidad, búsquedas y publicaciones desde {$location['locality']}.",
            'photo_url' => "https://example.com/demo-users/{$seq}.jpg",
            'linkedin' => "https://www.linkedin.com/in/{$slug}-demo-{$seq}",
            'website' => "https://example.com/profesionales/{$seq}",
            'facebook' => "{$slug}.demo",
            'instagram' => "@{$slug}.demo",
            'availability_notes' => ['Mañanas', 'Tardes', 'Fines de semana', 'A coordinar'][$seq % 4],
            'admin_notes' => "Usuario demo escenario {$seq}.",
        ];
    }

    private function resetDemoRelations(User $user): void
    {
        $user->workshopMemberships()->detach();
        UserVisibilitySetting::where('user_id', $user->id)->delete();
        UserDegree::where('user_id', $user->id)->delete();
        UserPosition::where('user_id', $user->id)->delete();
        Service::where('user_id', $user->id)->forceDelete();
        Need::where('user_id', $user->id)->forceDelete();
    }

    private function seedMemberships(User $user, Workshop $primary, $workshops, array $scenario, int $index, int $seq): void
    {
        $this->attachMembership($user, $primary, $scenario['role'], $scenario['membership_status'], $index);

        if ($scenario['second_workshop'] && $workshops->count() > 1) {
            $secondary = $workshops
                ->reject(fn (Workshop $workshop) => $workshop->id === $primary->id)
                ->values()
                ->get($seq % ($workshops->count() - 1));

            if ($secondary) {
                $this->attachMembership($user, $secondary, 'member', 'historical', $index);
            }
        }
    }

    private function attachMembership(User $user, Workshop $workshop, string $role, string $status, int $index): void
    {
        $user->workshopMemberships()->attach($workshop->id, [
            'role' => $role,
            'status' => $status,
            'requested_by_user' => in_array($status, ['pending', 'correction_requested'], true),
            'user_seen_at' => $status === 'correction_requested' ? null : Carbon::now()->subDays($index + 1),
            'correction_notes' => $status === 'correction_requested' ? 'Falta validar documentación de pertenencia.' : null,
        ]);
    }

    private function seedVisibility(User $user, string $preset): void
    {
        foreach (self::VISIBILITY_PRESETS[$preset] as $block => $visibility) {
            UserVisibilitySetting::create([
                'user_id' => $user->id,
                'block' => $block,
                'visibility' => $visibility,
            ]);
        }
    }

    private function seedDegrees(User $user, Workshop $workshop, array $scenario): void
    {
        UserDegree::create([
            'user_id' => $user->id,
            'workshop_id' => $workshop->id,
            'degree' => $scenario['degree'],
            'start_date' => Carbon::now()->subYears(4)->subDays($user->id % 365),
            'end_date' => null,
            'notes' => 'Grado demo vigente.',
        ]);

        if ($scenario['degree'] === 'maestro') {
            UserDegree::create([
                'user_id' => $user->id,
                'workshop_id' => $workshop->id,
                'degree' => 'companero',
                'start_date' => Carbon::now()->subYears(7),
                'end_date' => Carbon::now()->subYears(4)->subDay(),
                'notes' => 'Histórico demo.',
            ]);
        }
    }

    private function seedPositions(User $user, Workshop $workshop, $positions, array $scenario, int $seq): void
    {
        if ($positions->isEmpty() || $seq % 4 === 3) {
            return;
        }

        UserPosition::create([
            'user_id' => $user->id,
            'position_id' => $positions[$seq % $positions->count()]->id,
            'workshop_id' => $workshop->id,
            'start_date' => Carbon::now()->subYears(2)->subDays($seq % 120),
            'end_date' => $scenario['role'] === 'admin' ? null : ($seq % 2 === 0 ? null : Carbon::now()->subMonths(3)),
            'notes' => $scenario['role'] === 'admin' ? 'Cargo administrativo demo.' : 'Cargo demo.',
        ]);
    }

    private function seedPublications(User $user, $categories, ?User $admin, array $scenario, array $location, int $seq): void
    {
        foreach ($scenario['services'] as $offset => $s) {
            Service::create([
                'user_id' => $user->id,
                'service_category_id' => $categories->isNotEmpty() ? $categories[($seq + $offset) % $categories->count()]->id : null,
                'title' => $s['title'],
                'description' => $s['description'],
                'modality' => $s['modality'],
                'location' => "{$location['locality']}, {$location['province']}",
                'availability' => ['Lunes a viernes', 'Turnos por la tarde', 'Remoto con agenda previa'][$offset % 3],
                'conditions' => ['Sin cargo para consultas iniciales', 'Arancel preferencial', 'Derivación según disponibilidad'][$offset % 3],
                'visibility' => $s['visibility'],
                'status' => $s['status'],
                'published_at' => $s['published_at'],
                'expires_at' => $s['expires_at'],
            ]);
        }

        foreach ($scenario['needs'] as $offset => $n) {
            Need::create([
                'user_id' => $user->id,
                'service_category_id' => $categories->isNotEmpty() ? $categories[($seq + $offset + 3) % $categories->count()]->id : null,
                'title' => $n['title'],
                'description' => $n['description'],
                'location' => "{$location['locality']}, {$location['province']}",
                'urgency' => $n['urgency'],
                'visibility' => $n['visibility'],
                'status' => $n['status'],
                'published_at' => $n['published_at'],
                'expires_at' => $n['expires_at'],
            ]);
        }
    }

    private function serviceScenarios(int $seq): array
    {
        $catalog = [
            ['lifecycle' => 'active', 'visibility' => 'registered', 'modality' => 'both', 'title' => 'Asesoramiento profesional disponible'],
            ['lifecycle' => 'active', 'visibility' => 'anonymous', 'modality' => 'remoto', 'title' => 'Consulta reservada para hermanos'],
            ['lifecycle' => 'active', 'visibility' => 'registered', 'modality' => 'presencial', 'title' => 'Servicio presencial disponible'],
            ['lifecycle' => 'draft', 'visibility' => 'my_workshops', 'modality' => 'both', 'title' => 'Borrador de servicio en preparación'],
            ['lifecycle' => 'expired', 'visibility' => 'registered', 'modality' => 'presencial', 'title' => 'Servicio vencido de prueba'],
            ['lifecycle' => 'draft', 'visibility' => 'private', 'modality' => 'both', 'title' => 'Borrador de servicio privado'],
            ['lifecycle' => 'suspended', 'visibility' => 'my_workshops', 'modality' => 'remoto', 'title' => 'Servicio suspendido temporalmente'],
            ['lifecycle' => 'active', 'visibility' => 'registered', 'modality' => 'both', 'title' => 'Servicio activo destacado'],
            ['lifecycle' => 'suspended', 'visibility' => 'registered', 'modality' => 'presencial', 'title' => 'Servicio suspendido por el autor'],
            ['lifecycle' => 'expired', 'visibility' => 'my_workshops', 'modality' => 'both', 'title' => 'Servicio vencido para republicar'],
            ['lifecycle' => 'draft', 'visibility' => 'private', 'modality' => 'remoto', 'title' => 'Borrador de servicio remoto'],
        ];

        return [
            $this->publicationPayload($catalog[$seq % count($catalog)], 'service', $seq),
            $this->publicationPayload($catalog[($seq + 3) % count($catalog)], 'service', $seq + 1),
            $this->publicationPayload($catalog[($seq + 6) % count($catalog)], 'service', $seq + 2),
        ];
    }

    private function needScenarios(int $seq): array
    {
        $catalog = [
            ['lifecycle' => 'active', 'visibility' => 'registered', 'urgency' => 'medium', 'title' => 'Necesito recomendación profesional'],
            ['lifecycle' => 'active', 'visibility' => 'anonymous', 'urgency' => 'high', 'title' => 'Búsqueda urgente y reservada'],
            ['lifecycle' => 'active', 'visibility' => 'my_workshops', 'urgency' => 'low', 'title' => 'Necesidad con contactos sugeridos'],
            ['lifecycle' => 'suspended', 'visibility' => 'workshop', 'urgency' => 'medium', 'title' => 'Necesidad suspendida'],
            ['lifecycle' => 'expired', 'visibility' => 'registered', 'urgency' => 'low', 'title' => 'Necesidad vencida'],
            ['lifecycle' => 'suspended', 'visibility' => 'registered', 'urgency' => 'low', 'title' => 'Necesidad resuelta y suspendida'],
            ['lifecycle' => 'draft', 'visibility' => 'private', 'urgency' => 'low', 'title' => 'Borrador de necesidad privada'],
            ['lifecycle' => 'active', 'visibility' => 'registered', 'urgency' => 'high', 'title' => 'Necesidad urgente abierta'],
            ['lifecycle' => 'draft', 'visibility' => 'my_workshops', 'urgency' => 'medium', 'title' => 'Borrador de necesidad en preparación'],
            ['lifecycle' => 'expired', 'visibility' => 'workshop', 'urgency' => 'medium', 'title' => 'Necesidad vencida para republicar'],
            ['lifecycle' => 'draft', 'visibility' => 'private', 'urgency' => 'low', 'title' => 'Borrador de necesidad'],
        ];

        return [
            $this->publicationPayload($catalog[$seq % count($catalog)], 'need', $seq),
            $this->publicationPayload($catalog[($seq + 4) % count($catalog)], 'need', $seq + 1),
            $this->publicationPayload($catalog[($seq + 8) % count($catalog)], 'need', $seq + 2),
        ];
    }

    private function publicationPayload(array $base, string $type, int $seq): array
    {
        $now = Carbon::now();
        [$status, $publishedAt, $expiresAt] = match ($base['lifecycle']) {
            'active'    => ['active', $now->copy()->subDays(5), $now->copy()->addDays(30)],
            'expired'   => ['active', $now->copy()->subDays(100), $now->copy()->subDays(10)],
            'suspended' => ['suspended', $now->copy()->subDays(20), $now->copy()->addDays(20)],
            default     => ['draft', null, null],
        };

        $description = $type === 'service'
            ? 'Oferta demo para probar exploración, visibilidad y vencimiento.'
            : 'Necesidad demo para probar estados, urgencia y vencimiento.';

        return [
            'title' => $base['title'],
            'visibility' => $base['visibility'],
            'modality' => $base['modality'] ?? null,
            'urgency' => $base['urgency'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'description' => "{$description} Caso {$seq}.",
        ];
    }
}
