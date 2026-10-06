<?php

namespace App\Enum;

enum ProjectStepStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'Pas commencée',
            self::InProgress => 'En cours',
            self::Done => 'Faite',
        };
    }

    /**
     * Libellé au pluriel, utilisé pour les filtres de la page.
     */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::Todo => 'Pas commencées',
            self::InProgress => 'En cours',
            self::Done => 'Faites',
        };
    }
}
