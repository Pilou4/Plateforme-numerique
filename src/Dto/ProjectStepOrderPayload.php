<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Nouvel ordre des tâches : la liste complète des identifiants, de la première à la dernière.
 */
final readonly class ProjectStepOrderPayload
{
    /**
     * @param list<int> $ids
     */
    public function __construct(
        #[Assert\NotBlank(message: 'La liste des tâches est vide.')]
        #[Assert\All([new Assert\Type('integer'), new Assert\Positive()])]
        #[Assert\Unique(message: 'Une tâche apparaît plusieurs fois.')]
        public array $ids = [],
    ) {
    }
}
