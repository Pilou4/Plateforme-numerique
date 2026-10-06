<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Règles du logo d'un projet, réunies en une seule contrainte réutilisable :
 * PNG, JPG, WebP ou SVG, 2 Mo maximum.
 * (Assert\File et non Assert\Image, qui ne sait pas lire le SVG.)
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ProjectLogo extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\File(
                maxSize: '2M',
                mimeTypes: ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
                maxSizeMessage: 'Le logo ne doit pas dépasser {{ limit }} {{ suffix }}.',
                mimeTypesMessage: 'Le logo doit être une image PNG, JPG, WebP ou SVG.',
            ),
        ];
    }
}
