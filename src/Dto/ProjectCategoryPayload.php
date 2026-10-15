<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire d'une catégorie de projet (page Paramètres : ajout et modification).
 */
final readonly class ProjectCategoryPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire.', normalizer: 'trim')]
        #[Assert\Length(max: 100, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $name = null,
    ) {
    }
}
