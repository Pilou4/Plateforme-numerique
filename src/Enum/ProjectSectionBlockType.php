<?php

namespace App\Enum;

/**
 * Type d'un bloc de section de projet (colonne project_section_block.type).
 * Les valeurs, enregistrées en base et envoyées par l'éditeur, sont en français comme tout le contenu des tables.
 */
enum ProjectSectionBlockType: string
{
    case Paragraph = 'paragraphe';
    case Subtitle = 'sous_titre';
    case List = 'liste';
    case Table = 'tableau';
    case Tags = 'tags';
    case Definitions = 'definitions';
    case Images = 'images';
    case Documents = 'documents';

    public function label(): string
    {
        return match ($this) {
            self::Paragraph => 'Paragraphe',
            self::Subtitle => 'Sous-titre',
            self::List => 'Liste',
            self::Table => 'Tableau',
            self::Tags => 'Tags',
            self::Definitions => 'Définitions',
            self::Images => 'Images',
            self::Documents => 'Documents',
        };
    }

    /**
     * Phrase d'aide affichée dans le menu « Ajouter un bloc ».
     */
    public function help(): string
    {
        return match ($this) {
            self::Paragraph => 'Du texte : chaque ligne devient un paragraphe.',
            self::Subtitle => 'Un titre à l\'intérieur de la section.',
            self::List => 'À puces ou numérotée.',
            self::Table => 'En-têtes, lignes et colonnes.',
            self::Tags => 'Des mots-clés en pastilles.',
            self::Definitions => 'Des paires terme / définition.',
            self::Images => 'Une ou plusieurs images, avec légende.',
            self::Documents => 'PDF, Word, Excel… à télécharger.',
        };
    }

    /**
     * Les blocs dont le contenu est une liste de fichiers du projet (table project_section_block_file).
     */
    public function usesFiles(): bool
    {
        return self::Images === $this || self::Documents === $this;
    }
}
