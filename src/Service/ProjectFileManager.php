<?php

namespace App\Service;

use App\Dto\ProjectFilePayload;
use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Enum\ProjectFileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Ajout, modification et suppression des fichiers d'un projet
 * (la ligne en base et le fichier dans le stockage privé).
 */
final class ProjectFileManager
{
    private const string FILE_PREFIX = 'fichier';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectStorage $projectStorage,
    ) {
    }

    public function upload(Project $project, UploadedFile $uploadedFile, ProjectFilePayload $payload): ProjectFile
    {
        // Lu avant l'enregistrement : après move(), le fichier temporaire n'existe plus
        $mimeType = $uploadedFile->getMimeType() ?? 'application/octet-stream';
        $size = (int) $uploadedFile->getSize();
        $originalName = $this->cleanOriginalName($uploadedFile->getClientOriginalName());
        $type = ProjectFileType::fromMimeType($mimeType);

        $fileName = $this->projectStorage->store($project, $uploadedFile, self::FILE_PREFIX, $type->folder());

        $file = (new ProjectFile())
            ->setType($type)
            ->setTitle($this->cleanText($payload->title) ?? pathinfo($originalName, \PATHINFO_FILENAME))
            ->setDescription($this->cleanText($payload->description))
            ->setFileName($fileName)
            ->setOriginalName($originalName)
            ->setMimeType($mimeType)
            ->setSize($size);

        $project->addFile($file);
        $this->entityManager->persist($file);
        $this->entityManager->flush();

        return $file;
    }

    public function update(ProjectFile $file, ProjectFilePayload $payload): void
    {
        $title = $this->cleanText($payload->title);

        if (null === $title) {
            throw new \DomainException('Le titre est obligatoire.');
        }

        $file->setTitle($title);
        $file->setDescription($this->cleanText($payload->description));

        $this->entityManager->flush();
    }

    public function delete(ProjectFile $file): void
    {
        $project = $file->getProject();

        $this->projectStorage->delete($project, $file->getFileName(), $file->getType()->folder());
        $project->removeFile($file);
        $this->entityManager->remove($file);
        $this->entityManager->flush();
    }

    public function getPath(ProjectFile $file): string
    {
        return $this->projectStorage->getPath($file->getProject(), $file->getFileName(), $file->getType()->folder());
    }

    public function exists(ProjectFile $file): bool
    {
        return $this->projectStorage->exists($file->getProject(), $file->getFileName(), $file->getType()->folder());
    }

    private function cleanText(?string $text): ?string
    {
        $text = null === $text ? '' : trim($text);

        return '' === $text ? null : $text;
    }

    /**
     * Nom d'origine sans chemin ni caractères de contrôle, limité à 255 caractères.
     */
    private function cleanOriginalName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $name))) ?? '';

        return '' === $name ? 'fichier' : mb_substr($name, 0, 255);
    }
}
