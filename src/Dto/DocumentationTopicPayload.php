<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire d'un tutoriel (ajout et modification).
 */
final readonly class DocumentationTopicPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
        #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $title = null,

        #[Assert\Length(max: 255, maxMessage: 'Le résumé ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $summary = null,
    ) {
    }
}
