<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ajout ou retrait d'un projet des favoris : { "favorite": true }
 */
final readonly class ProjectFavoritePayload
{
    public function __construct(
        #[Assert\NotNull(message: 'Indique si le projet est un favori.')]
        public ?bool $favorite = null,
    ) {
    }
}
