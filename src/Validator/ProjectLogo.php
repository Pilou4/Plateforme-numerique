<?php

namespace App\Validator;

/**
 * Règles du logo d'un projet (voir ProjectImageFile).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ProjectLogo extends ProjectImageFile
{
    protected function subject(): string
    {
        return 'Le logo';
    }
}
