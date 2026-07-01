<?php

namespace App\Support;

/**
 * Reglas de progresión de grados masónicos: aprendiz -> companero -> maestro.
 * Un grado deja de ser vigente cuando inicia el siguiente; no se permiten
 * saltos, retrocesos, grados duplicados ni fechas fuera de orden.
 */
class DegreeProgression
{
    public const ORDER = ['aprendiz' => 0, 'companero' => 1, 'maestro' => 2];

    public const WORKSHOP_ERROR = 'El taller seleccionado debe ser uno de los talleres del Hermano.';

    public static function rank(string $degree): int
    {
        return self::ORDER[$degree];
    }

    /**
     * Valida que la lista completa de grados de un Hermano forme una progresión
     * estricta: ordenados por fecha de inicio, los grados deben ser exactamente
     * aprendiz, companero, maestro (prefijo), con fechas estrictamente crecientes.
     *
     * @param array<int,array{degree:string,start:string}> $degrees start en formato Y-m-d
     * @return string|null mensaje de error, o null si la progresión es válida
     */
    public static function validate(array $degrees): ?string
    {
        usort($degrees, fn ($a, $b) => strcmp($a['start'], $b['start']) ?: (self::ORDER[$a['degree']] <=> self::ORDER[$b['degree']]));

        $prevStart = null;
        foreach (array_values($degrees) as $i => $d) {
            if (self::ORDER[$d['degree']] !== $i) {
                return 'La progresión de grados debe ser aprendiz, luego compañero, luego maestro, sin saltos ni repeticiones.';
            }
            if ($prevStart !== null && strcmp($d['start'], $prevStart) <= 0) {
                return 'La fecha de inicio de cada grado debe ser posterior a la del grado anterior.';
            }
            $prevStart = $d['start'];
        }

        return null;
    }

    /** Próximo grado válido dado el conjunto de grados existentes (o null si ya es maestro). */
    public static function nextDegree(array $currentDegrees): ?string
    {
        $maxRank = -1;
        foreach ($currentDegrees as $d) {
            $maxRank = max($maxRank, self::ORDER[$d]);
        }
        $nextRank = $maxRank + 1;
        $byRank = array_flip(self::ORDER);

        return $byRank[$nextRank] ?? null;
    }
}
