<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Règles d'un fichier ajouté à un projet (image ou document), 10 Mo maximum.
 *
 * Le type est vérifié sur le contenu réel du fichier (type MIME), pas sur son extension.
 * L'absence de fichier est vérifiée dans le contrôleur : sans fichier, Symfony ne lance pas la validation.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ProjectFileUpload extends Compound
{
    public const string MAX_SIZE = '10M';

    /**
     * Types MIME acceptés, regroupés par famille.
     */
    public const array MIME_TYPES = [
        // Images
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/svg+xml',
        // PDF
        'application/pdf',
        // Word
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        // Excel
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // PowerPoint
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Texte et Markdown (un .md est le plus souvent détecté comme text/plain)
        'text/plain',
        'text/markdown',
        'text/x-markdown',
    ];

    protected function getConstraints(array $options): array
    {
        return [
            new Assert\File(
                maxSize: self::MAX_SIZE,
                mimeTypes: self::MIME_TYPES,
                maxSizeMessage: 'Le fichier ne doit pas dépasser {{ limit }} {{ suffix }}.',
                mimeTypesMessage: 'Ce type de fichier n\'est pas accepté : images (PNG, JPG, WebP, SVG), PDF, Word, Excel, PowerPoint, texte ou Markdown.',
                uploadIniSizeErrorMessage: 'Le fichier dépasse la taille autorisée par PHP ({{ limit }} {{ suffix }}) : il faut augmenter upload_max_filesize dans php.ini.',
            ),
        ];
    }
}
