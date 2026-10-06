<?php

namespace App\Service;

use App\Entity\Project;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stockage privé des fichiers des projets (logo, puis images et documents).
 *
 * Les fichiers sont rangés hors du dossier public, dans var/storage/projets/{id du projet}/ :
 * ils ne sont accessibles que par un contrôleur, qui pourra vérifier les droits d'accès.
 * Le dossier porte l'id du projet (et non le slug) pour ne pas bouger si le projet est renommé.
 */
final class ProjectStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/storage/projets')]
        private readonly string $storageDirectory,
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * Enregistre le fichier sous un nom unique et renvoie ce nom.
     */
    public function store(Project $project, UploadedFile $file, string $prefix): string
    {
        $extension = $file->guessExtension() ?? 'bin';
        $fileName = \sprintf('%s-%s.%s', $prefix, bin2hex(random_bytes(8)), $extension);

        $file->move($this->getProjectDirectory($project), $fileName);

        return $fileName;
    }

    public function getPath(Project $project, string $fileName): string
    {
        return $this->getProjectDirectory($project).'/'.basename($fileName);
    }

    public function exists(Project $project, string $fileName): bool
    {
        return $this->filesystem->exists($this->getPath($project, $fileName));
    }

    public function delete(Project $project, string $fileName): void
    {
        $this->filesystem->remove($this->getPath($project, $fileName));
    }

    private function getProjectDirectory(Project $project): string
    {
        return $this->storageDirectory.'/'.$project->getId();
    }
}
