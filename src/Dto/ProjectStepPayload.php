<?php

namespace App\Dto;

use App\Enum\ProjectStepPriority;
use App\Enum\ProjectStepStatus;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données envoyées par la page pour créer (POST) ou modifier (PATCH) une tâche.
 * En création, seuls title, description et priority sont utilisés.
 * En modification, un champ absent (null) n'est pas modifié.
 */
final readonly class ProjectStepPayload
{
    public const string GROUP_CREATE = 'create';

    public function __construct(
        #[Assert\NotNull(message: 'Le titre est obligatoire.', groups: [self::GROUP_CREATE])]
        #[Assert\NotBlank(message: 'Le titre est obligatoire.', allowNull: true)]
        #[Assert\Length(max: 255, maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.')]
        public ?string $title = null,

        public ?string $description = null,

        public ?ProjectStepStatus $status = null,

        public ?ProjectStepPriority $priority = null,

        #[Assert\PositiveOrZero(message: 'Le temps passé ne peut pas être négatif.')]
        #[Assert\LessThanOrEqual(value: 1000000, message: 'Le temps passé est trop grand.')]
        public ?int $timeSpent = null,
    ) {
    }
}
