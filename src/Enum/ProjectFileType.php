<?php

namespace App\Enum;

/**
 * Type d'un fichier de projet : déduit du fichier envoyé, il décide du sous-dossier
 * de stockage et du bloc où le fichier est affiché (galerie ou liste).
 */
enum ProjectFileType: string
{
    case Image = 'image';
    case Document = 'document';

    /**
     * Les images sont reconnues à leur type MIME (image/png, image/svg+xml…).
     */
    public static function fromMimeType(string $mimeType): self
    {
        return str_starts_with($mimeType, 'image/') ? self::Image : self::Document;
    }

    /**
     * Sous-dossier de stockage : var/storage/projets/{id}/images ou /documents
     */
    public function folder(): string
    {
        return match ($this) {
            self::Image => 'images',
            self::Document => 'documents',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Document => 'Document',
        };
    }
}
