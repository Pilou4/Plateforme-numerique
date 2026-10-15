<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une section de projet envoyée par l'éditeur (ajout et modification) : son titre et tous ses blocs,
 * dans l'ordre. La section est réenregistrée en entier à chaque fois.
 *
 * Le contenu de chaque bloc est vérifié par ProjectSectionBlockBuilder (règles propres à chaque type) :
 *   {"type": "paragraphe",  "text": "Ligne 1\nLigne 2"}
 *   {"type": "sous_titre",  "text": "Les tables"}
 *   {"type": "liste",       "ordered": false, "items": ["Docker", "Git"]}
 *   {"type": "tableau",     "columns": ["Table", "Rôle"], "rows": [["project", "Un projet"]]}
 *   {"type": "tags",        "tags": ["MySQL", "Doctrine"]}
 *   {"type": "definitions", "items": [{"term": "Entité", "definition": "Une classe…"}]}
 *   {"type": "images",      "files": [{"id": 12, "caption": "Schéma"}]}
 *   {"type": "documents",   "files": [{"id": 15, "caption": null}]}
 */
final readonly class ProjectSectionPayload
{
    public const int MAX_BLOCKS = 100;

    /**
     * @param list<array<string, mixed>> $blocks
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le titre de la section est obligatoire.', normalizer: 'trim')]
        #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $title = null,

        #[Assert\Count(max: self::MAX_BLOCKS, maxMessage: 'Une section ne peut pas avoir plus de {{ limit }} blocs.')]
        public array $blocks = [],
    ) {
    }
}
