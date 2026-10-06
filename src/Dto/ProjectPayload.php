<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire d'ajout d'un projet (le logo arrive à part, en fichier).
 */
final readonly class ProjectPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du projet est obligatoire.')]
        #[Assert\Length(max: 255, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $name = null,

        #[Assert\Length(max: 255, maxMessage: 'Le résumé ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $summary = null,
    ) {
    }
}
