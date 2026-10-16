<?php

namespace App\Enum;

/**
 * Les images d'un projet, toutes facultatives et rangées dans son stockage privé
 * (var/storage/projets/{id}/) :
 *   - le logo        : carré, affiché sur la carte et dans l'en-tête du projet ;
 *   - le logo long   : horizontal (le logo avec le nom à côté, par exemple) ;
 *   - l'avatar       : petite image ronde.
 *
 * La valeur est le nom du champ « fichier » du formulaire (logo, logoWide, avatar).
 */
enum ProjectImage: string
{
    case Logo = 'logo';
    case LogoWide = 'logoWide';
    case Avatar = 'avatar';

    public function label(): string
    {
        return match ($this) {
            self::Logo => 'Logo',
            self::LogoWide => 'Logo long',
            self::Avatar => 'Avatar',
        };
    }

    /**
     * Début du nom du fichier enregistré : logo-a1b2….png, logo-long-….png, avatar-….png
     */
    public function filePrefix(): string
    {
        return match ($this) {
            self::Logo => 'logo',
            self::LogoWide => 'logo-long',
            self::Avatar => 'avatar',
        };
    }

    /**
     * L'image dont l'adresse se termine par ce nom (logo, logo-long, avatar), comme /app/projet/{slug}/logo-long.
     */
    public static function tryFromUrlName(string $name): ?self
    {
        foreach (self::cases() as $image) {
            if ($image->filePrefix() === $name) {
                return $image;
            }
        }

        return null;
    }

    /**
     * Case à cocher « Supprimer … » du formulaire de modification.
     */
    public function removeField(): string
    {
        return 'remove'.ucfirst($this->value);
    }

    /**
     * Route qui affiche l'image (App\Controller\App\ProjectController).
     */
    public function routeName(): string
    {
        return match ($this) {
            self::Logo => 'app_project_logo',
            self::LogoWide => 'app_project_logo_wide',
            self::Avatar => 'app_project_avatar',
        };
    }
}
