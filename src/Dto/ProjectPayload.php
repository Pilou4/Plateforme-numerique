<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire d'un projet (les images arrivent à part, en fichiers).
 *
 * Le formulaire est envoyé en multipart/form-data : tous les champs arrivent en texte,
 * d'où category en chaîne (« 3 », ou « » pour aucune catégorie).
 */
final readonly class ProjectPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du projet est obligatoire.')]
        #[Assert\Length(max: 255, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $name = null,

        /**
         * Description courte, affichée sur la carte du projet.
         */
        #[Assert\Length(max: 255, maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $summary = null,

        /**
         * Code du statut (table project_state). Absent : le premier statut de la liste à la création,
         * inchangé en modification. Son existence est vérifiée par ProjectManager (les statuts
         * se gèrent depuis l'app : la liste n'est pas connue à l'avance).
         */
        #[Assert\Length(max: 30, maxMessage: 'Ce statut n\'existe pas.')]
        public ?string $state = null,

        /**
         * Identifiant de la catégorie (table project_category). Vide : aucune catégorie.
         */
        #[Assert\Regex(pattern: '/^\d*$/', message: 'Cette catégorie n\'existe pas.')]
        public ?string $category = null,
    ) {
    }
}
