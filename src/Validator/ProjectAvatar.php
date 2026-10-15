<?php

namespace App\Validator;

/**
 * Règles de l'avatar d'un projet (voir ProjectImageFile).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ProjectAvatar extends ProjectImageFile
{
    protected function subject(): string
    {
        return 'L\'avatar';
    }
}
