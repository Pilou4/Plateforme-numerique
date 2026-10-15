<?php

namespace App\Enum;

/**
 * Couleur de la pastille d'un statut de projet (table project_state, colonne color).
 * Les valeurs, enregistrées en base, sont en français comme tout le contenu des tables ;
 * cssModifier() donne le nom de la variante CSS (.project-state--blue…), en anglais comme le code.
 */
enum ProjectStateColor: string
{
    case Blue = 'bleu';
    case Green = 'vert';
    case Violet = 'violet';
    case Orange = 'orange';
    case Red = 'rouge';
    case Grey = 'gris';

    public function label(): string
    {
        return match ($this) {
            self::Blue => 'Bleu',
            self::Green => 'Vert',
            self::Violet => 'Violet',
            self::Orange => 'Orange',
            self::Red => 'Rouge',
            self::Grey => 'Gris',
        };
    }

    public function cssModifier(): string
    {
        return match ($this) {
            self::Blue => 'blue',
            self::Green => 'green',
            self::Violet => 'violet',
            self::Orange => 'orange',
            self::Red => 'red',
            self::Grey => 'grey',
        };
    }

    /**
     * Toutes les valeurs (pour la validation du formulaire).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $color): string => $color->value, self::cases());
    }
}
