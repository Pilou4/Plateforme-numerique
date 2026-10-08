<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Déplacement d'une section d'un cran : vers le haut ou vers le bas.
 */
final readonly class SectionMovePayload
{
    public const string UP = 'up';
    public const string DOWN = 'down';

    public function __construct(
        #[Assert\NotNull(message: 'La direction est obligatoire.')]
        #[Assert\Choice(choices: [self::UP, self::DOWN], message: 'La direction doit être « up » ou « down ».')]
        public ?string $direction = null,
    ) {
    }
}
