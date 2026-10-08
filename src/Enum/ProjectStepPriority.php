<?php

namespace App\Enum;

/**
 * Priorité d'une tâche ou d'une sous-tâche.
 * Les valeurs (enregistrées en base et envoyées par l'API) sont en français, comme tout le contenu des tables.
 */
enum ProjectStepPriority: string
{
    case Low = 'basse';
    case Normal = 'normale';
    case High = 'haute';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Normal => 'Normale',
            self::High => 'Haute',
        };
    }
}
