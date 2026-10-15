<?php

namespace App\Dto;

use App\Enum\ProjectStateColor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire d'un statut de projet (page Paramètres : ajout et modification).
 */
final readonly class ProjectStatePayload
{
    public function __construct(
        /**
         * Nom affiché sur la carte d'un projet : « En pause ».
         */
        #[Assert\NotBlank(message: 'Le nom du statut est obligatoire.', normalizer: 'trim')]
        #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $label = null,

        /**
         * Nom au pluriel, pour les filtres de la liste des projets : « En pause ». Vide : le même que le nom.
         */
        #[Assert\Length(max: 50, maxMessage: 'Le nom au pluriel ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $pluralLabel = null,

        /**
         * Couleur de la pastille (valeur de ProjectStateColor : bleu, vert…).
         */
        #[Assert\NotBlank(message: 'Choisis une couleur.')]
        #[Assert\Choice(callback: [ProjectStateColor::class, 'values'], message: 'Cette couleur n\'existe pas.')]
        public ?string $color = null,
    ) {
    }
}
