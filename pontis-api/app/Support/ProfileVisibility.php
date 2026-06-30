<?php

namespace App\Support;

use App\Models\User;

/**
 * Resuelve si un Hermano (viewer) puede ver una sección de otro (subject),
 * según el nivel de visibilidad elegido por el subject y la relación de
 * talleres entre ambos. La búsqueda de Hermanos se rige por estas reglas,
 * de forma independiente a la pertenencia de talleres del que busca.
 */
class ProfileVisibility
{
    /** @var int[] IDs de talleres activos del que mira. */
    public array $viewerWorkshopIds;

    public function __construct(public User $viewer)
    {
        $this->viewerWorkshopIds = $viewer->workshops()->pluck('workshops.id')->all();
    }

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
            'private'                                => false,
            'registered'                             => true,
            'my_workshops', 'talleres_seleccionados' => $this->sharesWorkshop($subjectWorkshopIds),
            'workshop'                               => $this->inPrincipal($subjectPrincipalId),
            'anonymous'                              => false, // la identidad no se revela por nombre
            default                                  => $this->inPrincipal($subjectPrincipalId), // default = mi taller principal
        };
    }

    /** @param int[] $subjectWorkshopIds */
    private function sharesWorkshop(array $subjectWorkshopIds): bool
    {
        return count(array_intersect($this->viewerWorkshopIds, $subjectWorkshopIds)) > 0;
    }

    private function inPrincipal(?int $subjectPrincipalId): bool
    {
        return $subjectPrincipalId !== null
            && in_array($subjectPrincipalId, $this->viewerWorkshopIds, true);
    }
}
