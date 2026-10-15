<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Règles communes aux images d'un projet (logo, logo long, avatar) :
 * PNG, JPG, WebP ou SVG, 2 Mo maximum.
 * (Assert\File et non Assert\Image, qui ne sait pas lire le SVG.)
 *
 * Chaque image a sa propre contrainte (ProjectLogo, ProjectLogoWide, ProjectAvatar),
 * pour que le message d'erreur dise de quelle image il s'agit.
 */
abstract class ProjectImageFile extends Compound
{
    /**
     * Début des messages d'erreur : « Le logo », « Le logo long », « L'avatar ».
     */
    abstract protected function subject(): string;

    protected function getConstraints(array $options): array
    {
        return [
            new Assert\File(
                maxSize: '2M',
                mimeTypes: ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
                maxSizeMessage: $this->subject().' ne doit pas dépasser {{ limit }} {{ suffix }}.',
                mimeTypesMessage: $this->subject().' doit être une image PNG, JPG, WebP ou SVG.',
            ),
        ];
    }
}
