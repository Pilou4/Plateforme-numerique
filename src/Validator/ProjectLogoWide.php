<?php

namespace App\Validator;

/**
 * Règles du logo long d'un projet (voir ProjectImageFile).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class ProjectLogoWide extends ProjectImageFile
{
    protected function subject(): string
    {
        return 'Le logo long';
    }
}
