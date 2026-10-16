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

        /**
         * Adresse du dépôt (GitHub…). Absente : inchangée en modification (les cartes de la page Projets
         * ne l'envoient pas) ; vide : retirée.
         */
        #[Assert\Url(message: 'L\'adresse du dépôt doit commencer par https:// ou http://.', protocols: ['http', 'https'], requireTld: true, normalizer: 'trim')]
        #[Assert\Length(max: 255, maxMessage: 'L\'adresse du dépôt ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $repositoryUrl = null,

        /**
         * Adresse du site. Même règle que repositoryUrl.
         */
        #[Assert\Url(message: 'L\'adresse du site doit commencer par https:// ou http://.', protocols: ['http', 'https'], requireTld: true, normalizer: 'trim')]
        #[Assert\Length(max: 255, maxMessage: 'L\'adresse du site ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $websiteUrl = null,
    ) {
    }
}
