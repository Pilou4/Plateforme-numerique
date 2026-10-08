<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire d'un terme du glossaire (ajout et modification).
 */
final readonly class GlossaryTermPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le terme est obligatoire.')]
        #[Assert\Length(max: 255, maxMessage: 'Le terme ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $term = null,

        #[Assert\NotBlank(message: 'La définition est obligatoire.')]
        public ?string $definition = null,
    ) {
    }
}
