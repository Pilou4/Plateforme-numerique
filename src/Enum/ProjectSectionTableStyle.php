<?php

namespace App\Enum;

/**
 * Style d'un bloc tableau (colonne project_section_block.table_style), choisi dans l'éditeur de la section.
 * Les valeurs enregistrées en base sont en français ; le suffixe CSS (cssModifier) est en anglais :
 * le tableau reçoit la classe section-block__table-wrap--{cssModifier} (styles dans app.css).
 *
 * L'ordre des cas est celui de la liste « Style » de l'éditeur ; le premier est le style par défaut.
 */
enum ProjectSectionTableStyle: string
{
    case Finder = 'liste_finder';
    case NavyHead = 'entete_marine';
    case Spreadsheet = 'gris_tableur';
    case SoftCard = 'carte_douce';
    case BlueHead = 'entete_bleu';
    case Grid = 'grille';
    case RowCards = 'lignes_cartes';
    case Dark = 'sombre';
    case GradientLine = 'trait_degrade';
    case PillHead = 'entete_pilule';
    case LabelColumn = 'fiche';

    /**
     * Style d'un nouveau tableau, et des tableaux enregistrés avant le choix du style.
     */
    public static function default(): self
    {
        return self::Finder;
    }

    public function label(): string
    {
        return match ($this) {
            self::Finder => 'Liste Finder',
            self::NavyHead => 'En-tête marine',
            self::Spreadsheet => 'Gris tableur',
            self::SoftCard => 'Carte douce',
            self::BlueHead => 'En-tête bleu',
            self::Grid => 'Grille',
            self::RowCards => 'Lignes en cartes',
            self::Dark => 'Sombre',
            self::GradientLine => 'Trait dégradé',
            self::PillHead => 'En-tête pilule',
            self::LabelColumn => 'Fiche',
        };
    }

    /**
     * Suffixe de la classe CSS : section-block__table-wrap--{cssModifier}.
     */
    public function cssModifier(): string
    {
        return match ($this) {
            self::Finder => 'finder',
            self::NavyHead => 'navy-head',
            self::Spreadsheet => 'spreadsheet',
            self::SoftCard => 'soft-card',
            self::BlueHead => 'blue-head',
            self::Grid => 'grid',
            self::RowCards => 'row-cards',
            self::Dark => 'dark',
            self::GradientLine => 'gradient-line',
            self::PillHead => 'pill-head',
            self::LabelColumn => 'label-column',
        };
    }
}
