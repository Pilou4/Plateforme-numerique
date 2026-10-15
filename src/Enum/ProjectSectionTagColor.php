<?php

namespace App\Enum;

/**
 * Couleur d'un tag d'un bloc tags (colonne project_section_tag.color), choisie tag par tag dans l'éditeur.
 * Les valeurs enregistrées en base sont en français ; le suffixe CSS (cssModifier) est en anglais :
 * le tag reçoit la classe section-block__tag--{cssModifier} (couleurs dans app.css).
 *
 * L'ordre des cas est celui des pastilles de l'éditeur ; le premier est la couleur par défaut.
 */
enum ProjectSectionTagColor: string
{
    case Blue = 'bleu';
    case Green = 'vert';
    case Violet = 'violet';
    case Orange = 'orange';
    case Red = 'rouge';
    case Grey = 'gris';
    case Cyan = 'cyan';
    case Yellow = 'jaune';
    case Pink = 'rose';

    /**
     * Couleur d'un nouveau tag, et des tags enregistrés avant le choix de la couleur.
     */
    public static function default(): self
    {
        return self::Blue;
    }

    public function label(): string
    {
        return match ($this) {
            self::Blue => 'Bleu',
            self::Green => 'Vert',
            self::Violet => 'Violet',
            self::Orange => 'Orange',
            self::Red => 'Rouge',
            self::Grey => 'Gris',
            self::Cyan => 'Cyan',
            self::Yellow => 'Jaune',
            self::Pink => 'Rose',
        };
    }

    /**
     * Suffixe de la classe CSS : section-block__tag--{cssModifier}.
     */
    public function cssModifier(): string
    {
        return match ($this) {
            self::Blue => 'blue',
            self::Green => 'green',
            self::Violet => 'violet',
            self::Orange => 'orange',
            self::Red => 'red',
            self::Grey => 'grey',
            self::Cyan => 'cyan',
            self::Yellow => 'yellow',
            self::Pink => 'pink',
        };
    }
}
