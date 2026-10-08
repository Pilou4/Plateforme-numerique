<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Titre et description d'un fichier de projet (le fichier lui-même arrive à part).
 *
 * À l'ajout, un titre vide est remplacé par le nom du fichier envoyé.
 */
final readonly class ProjectFilePayload
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $title = null,

        #[Assert\Length(max: 2000, maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $description = null,
    ) {
    }
}
