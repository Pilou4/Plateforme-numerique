<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire d'une section (tutoriel ou présentation d'un projet).
 */
final readonly class SectionPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
        #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $title = null,

        #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
        public ?string $content = null,
    ) {
    }
}
