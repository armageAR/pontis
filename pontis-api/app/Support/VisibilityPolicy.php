<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserVisibilitySetting;
use Illuminate\Support\Collection;

/**
 * Política central de visibilidad de Pontis.
 *
 * Toda decisión sobre qué puede ver un Hermano (viewer) de otro (subject)
 * debe resolverse acá: bloques de perfil, identidad anónima, alcance de
 * publicaciones y payloads filtrados. Los controladores no deben duplicar
 * estas reglas inline.
 *
 * Reglas núcleo:
 * - Privado por defecto: ante nivel desconocido se asume 'workshop'.
 * - La identidad se evalúa en dos pasos: audiencia configurada primero;
 *   el flag de aparición anónima solo habilita un resultado enmascarado
 *   para quienes no califican, nunca revela identidad.
 */
class VisibilityPolicy
{
    /** Nombre mostrado cuando la identidad no es visible. */
    public const MASKED_NAME = 'Hermano registrado';

    /** Nivel asumido cuando el subject no configuró un bloque. */
    public const DEFAULT_LEVEL = 'workshop';

    /** Bloques de perfil configurables. */
    public const PROFILE_BLOCKS = ['identity', 'masonic', 'contact', 'location', 'profession', 'bio', 'degrees', 'positions'];

    /** Niveles de visibilidad de publicaciones que un viewer registrado siempre puede ver. */
    public const PUBLICATION_OPEN_LEVELS = ['registered', 'anonymous'];

    /** @var int[] IDs de talleres del que mira. */
    public array $viewerWorkshopIds;

    /** @var array<int, Collection> Cache de settings por subject (evita N+1). */
    private array $settingsCache = [];

    public function __construct(public User $viewer)
    {
        $this->viewerWorkshopIds = $viewer->workshops()->pluck('workshops.id')->all();
    }

    // ── Decisión núcleo ───────────────────────────────────────────────────────

    /**
     * ¿El viewer puede ver una sección con el nivel dado del subject?
     *
     * @param int[] $subjectWorkshopIds
     */
    public function canSee(string $level, int $subjectId, array $subjectWorkshopIds, ?int $subjectPrincipalId): bool
    {
        if ($this->viewer->id === $subjectId) {
            return true; // uno mismo ve todo lo suyo
        }

        return match ($level) {
            'private'      => false,
            'registered'   => true,
            'my_workshops' => count(array_intersect($this->viewerWorkshopIds, $subjectWorkshopIds)) > 0,
            'workshop'     => $this->inPrincipal($subjectPrincipalId),
            'anonymous'    => false, // la identidad no se revela por nombre
            default        => $this->inPrincipal($subjectPrincipalId), // default = mi taller principal
        };
    }

    /** Nivel configurado de un bloque dentro de una colección de settings del subject. */
    public static function blockLevel(?Collection $settings, string $block): string
    {
        return optional($settings?->firstWhere('block', $block))->visibility ?? self::DEFAULT_LEVEL;
    }

    // ── Settings del subject (con cache para lotes) ───────────────────────────

    /** Precarga settings de varios subjects en una sola query. */
    public function primeSettings(iterable $userIds): void
    {
        $ids = collect($userIds)->unique()->reject(fn ($id) => array_key_exists((int) $id, $this->settingsCache));
        if ($ids->isEmpty()) return;
        $grouped = UserVisibilitySetting::whereIn('user_id', $ids)->get()->groupBy('user_id');
        foreach ($ids as $id) {
            $this->settingsCache[(int) $id] = $grouped->get($id) ?? collect();
        }
    }

    public function settingsFor(int $userId): Collection
    {
        if (! array_key_exists($userId, $this->settingsCache)) {
            $this->settingsCache[$userId] = UserVisibilitySetting::where('user_id', $userId)->get();
        }
        return $this->settingsCache[$userId];
    }

    // ── Decisiones por bloque e identidad ─────────────────────────────────────

    /** ¿El viewer puede ver un bloque del perfil del subject? */
    public function canSeeBlock(User $subject, string $block, ?Collection $settings = null): bool
    {
        $settings ??= $this->settingsFor($subject->id);
        [$workshopIds, $principalId] = $this->subjectContext($subject);
        return $this->canSee(self::blockLevel($settings, $block), $subject->id, $workshopIds, $principalId);
    }

    /**
     * Resolución de identidad en dos pasos.
     *
     * @return object{visible: bool, anonymous: bool} visible: el viewer ve la
     *   identidad real; anonymous: no la ve pero el subject habilitó aparecer
     *   enmascarado. Si ambos son false, el subject no debe aparecer.
     */
    public function identityFor(User $subject, ?Collection $settings = null): object
    {
        $settings ??= $this->settingsFor($subject->id);
        [$workshopIds, $principalId] = $this->subjectContext($subject);

        $idSetting = $settings->firstWhere('block', 'identity');
        $level = $idSetting->visibility ?? self::DEFAULT_LEVEL;
        $anonSearch = (bool) ($idSetting->anonymous_search ?? false);

        $visible = $subject->id === $this->viewer->id
            || $this->canSee($level, $subject->id, $workshopIds, $principalId);

        return (object) ['visible' => $visible, 'anonymous' => ! $visible && $anonSearch];
    }

    /** Nombre a mostrar del subject para este viewer (real o enmascarado). */
    public function displayName(User $subject, ?Collection $settings = null): string
    {
        return $this->identityFor($subject, $settings)->visible ? $subject->name : self::MASKED_NAME;
    }

    // ── Payloads filtrados ────────────────────────────────────────────────────

    /**
     * Ficha de un Hermano filtrada por lo que este viewer puede ver.
     * Es el payload de perfil público interno (PublicProfileController).
     */
    public function profilePayload(User $subject): array
    {
        $settings = $this->settingsFor($subject->id);
        $subject->loadMissing([
            'workshops:id,name,number',
            'userDegrees.workshop:id,name,number',
            'userPositions.position:id,name',
            'userPositions.workshop:id,name,number',
        ]);

        $can = fn (string $block) => $this->canSeeBlock($subject, $block, $settings);
        $identityVisible = $this->identityFor($subject, $settings)->visible;

        $data = [
            'id'        => $subject->id,
            'name'      => $identityVisible ? $subject->name : self::MASKED_NAME,
            'anonymous' => ! $identityVisible,
            'can_request_contact' => \App\Http\Controllers\ContactConsentController::sourceAllowed($subject, 'search'),
            'workshops' => $subject->workshops, // el taller es información institucional
        ];

        if ($identityVisible)   { $data['last_name'] = $subject->last_name; $data['masonic_id'] = $subject->masonic_id; }
        if ($can('masonic'))    { $data['masonic_status'] = $subject->masonic_status; $data['initiation_date'] = $subject->initiation_date; }
        if ($can('contact'))    { $data['phone'] = $subject->phone; $data['whatsapp'] = $subject->whatsapp; $data['alternative_email'] = $subject->alternative_email; }
        if ($can('location'))   { $data['province'] = $subject->province; $data['locality'] = $subject->locality; }
        if ($can('profession')) { $data['profession'] = $subject->profession; $data['occupation'] = $subject->occupation; $data['company'] = $subject->company; }
        if ($can('bio'))        { $data['bio'] = $subject->bio; }
        if ($can('degrees')) {
            $data['degrees'] = $subject->userDegrees
                ->where('validation_status', 'validated')
                ->values()
                ->each->makeHidden(['validation_status', 'validator_id', 'validated_at', 'validation_notes']);
        }
        if ($can('positions')) {
            $data['positions'] = $subject->userPositions
                ->where('validation_status', 'validated')
                ->values()
                ->each->makeHidden(['validation_status', 'validator_id', 'validated_at', 'validation_notes']);
        }

        return $data;
    }

    // ── Publicaciones (servicios y necesidades) ───────────────────────────────

    /**
     * Aplica el alcance de visibilidad de publicaciones para este viewer.
     * Una publicación aparece si su visibilidad es abierta a registrados, o si
     * el viewer comparte taller (principal o no) con el autor según el nivel.
     */
    public function scopePublicationVisibility($query): void
    {
        $viewerWorkshopIds = collect($this->viewerWorkshopIds);

        $query->where(function ($q) use ($viewerWorkshopIds) {
            $q->whereIn('visibility', self::PUBLICATION_OPEN_LEVELS);

            if ($viewerWorkshopIds->isNotEmpty()) {
                $ownerIdsInPrincipalWorkshops = User::whereHas('workshops', function ($wq) use ($viewerWorkshopIds) {
                    $wq->whereIn('workshops.id', $viewerWorkshopIds)
                        ->where('user_workshop.is_principal', true);
                })->pluck('id');

                $ownerIdsInSameWorkshops = User::whereHas('workshops', function ($wq) use ($viewerWorkshopIds) {
                    $wq->whereIn('workshops.id', $viewerWorkshopIds);
                })->pluck('id');

                if ($ownerIdsInPrincipalWorkshops->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($ownerIdsInPrincipalWorkshops) {
                        $sub->where('visibility', 'workshop')
                            ->whereIn('user_id', $ownerIdsInPrincipalWorkshops);
                    });
                }

                if ($ownerIdsInSameWorkshops->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($ownerIdsInSameWorkshops) {
                        $sub->where('visibility', 'my_workshops')
                            ->whereIn('user_id', $ownerIdsInSameWorkshops);
                    });
                }
            }
        });
    }

    /**
     * Enmascara la identidad del autor en un resultado publicado con
     * visibilidad anónima. No revela nombre, apellido ni id del autor.
     */
    public function maskAnonymousAuthor($publication, bool $withProfession = false)
    {
        if ($publication->visibility !== 'anonymous') {
            return $publication;
        }

        $masked = [
            'id'        => null,
            'name'      => self::MASKED_NAME,
            'last_name' => null,
        ];
        if ($withProfession) {
            $masked['profession'] = $publication->user?->profession;
        }
        $masked['locality']  = $publication->user?->locality;
        $masked['province']  = $publication->user?->province;
        $masked['anonymous'] = true;

        // Importante: hay que descargar la relación antes de asignar el atributo.
        // Si la relación queda cargada, pisa al atributo en la serialización y
        // la identidad real del autor se filtraría en la respuesta.
        $publication->unsetRelation('user');
        $publication->user = $masked;
        $publication->user_id = null;

        return $publication;
    }

    /**
     * Vista previa de qué datos del autor quedarán visibles al publicar con
     * una visibilidad dada. Refleja lo que exponen los listados publicados.
     */
    public static function publicationPreview(User $author, string $visibility): array
    {
        $anonymous = $visibility === 'anonymous';

        $audience = match ($visibility) {
            'private'      => 'Solo vos',
            'workshop'     => 'Hermanos de tu taller principal',
            'my_workshops' => 'Hermanos de tus talleres',
            'registered'   => 'Masones registrados y validados',
            'anonymous'    => 'Masones registrados, sin revelar tu identidad',
            default        => 'Hermanos de tu taller principal',
        };

        return [
            'visibility' => $visibility,
            'audience'   => $audience,
            'anonymous'  => $anonymous,
            'author'     => [
                'name'       => $anonymous ? self::MASKED_NAME : $author->name,
                'last_name'  => $anonymous ? null : $author->last_name,
                'profession' => $author->profession,
                'locality'   => $author->locality,
                'province'   => $author->province,
            ],
        ];
    }

    // ── Internos ──────────────────────────────────────────────────────────────

    /** @return array{0: int[], 1: ?int} [workshopIds, principalId] del subject. */
    private function subjectContext(User $subject): array
    {
        $subject->loadMissing('workshops');
        $workshopIds = $subject->workshops->pluck('id')->all();
        $principalId = optional($subject->workshops->first(fn ($w) => (bool) ($w->pivot->is_principal ?? false)))->id;
        return [$workshopIds, $principalId];
    }

    private function inPrincipal(?int $subjectPrincipalId): bool
    {
        return $subjectPrincipalId !== null
            && in_array($subjectPrincipalId, $this->viewerWorkshopIds, true);
    }
}
