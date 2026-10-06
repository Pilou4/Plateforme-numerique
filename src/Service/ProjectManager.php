<?php

namespace App\Service;

use App\Dto\ProjectPayload;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Création et modification des projets : nom unique, slug calculé à partir du nom, logo facultatif.
 */
final class ProjectManager
{
    private const string LOGO_PREFIX = 'logo';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository,
        private readonly SluggerInterface $slugger,
        private readonly ProjectStorage $projectStorage,
    ) {
    }

    /**
     * @throws \DomainException si un projet porte déjà ce nom
     */
    public function create(ProjectPayload $payload, ?UploadedFile $logo): Project
    {
        $project = new Project();
        $this->applyName($project, (string) $payload->name);
        $project->setSummary($this->cleanSummary($payload->summary));

        $this->entityManager->persist($project);
        // Le projet doit avoir un id avant d'enregistrer le logo (son dossier porte l'id)
        $this->entityManager->flush();

        if (null !== $logo) {
            $project->setLogo($this->projectStorage->store($project, $logo, self::LOGO_PREFIX));
            $this->entityManager->flush();
        }

        return $project;
    }

    /**
     * Le slug suit le nom : s'il change, l'URL du projet change aussi.
     *
     * @param UploadedFile|null $logo       nouveau logo (remplace l'ancien)
     * @param bool              $removeLogo true pour supprimer le logo actuel
     *
     * @throws \DomainException si un autre projet porte déjà ce nom
     */
    public function update(Project $project, ProjectPayload $payload, ?UploadedFile $logo, bool $removeLogo): Project
    {
        $this->applyName($project, (string) $payload->name);
        $project->setSummary($this->cleanSummary($payload->summary));

        if (null !== $logo) {
            $this->replaceLogo($project, $this->projectStorage->store($project, $logo, self::LOGO_PREFIX));
        } elseif ($removeLogo) {
            $this->replaceLogo($project, null);
        }

        $this->entityManager->flush();

        return $project;
    }

    /**
     * Change le logo et supprime l'ancien fichier du stockage.
     */
    private function replaceLogo(Project $project, ?string $newLogo): void
    {
        $oldLogo = $project->getLogo();
        $project->setLogo($newLogo);

        if (null !== $oldLogo) {
            $this->projectStorage->delete($project, $oldLogo);
        }
    }

    /**
     * Le slug suit le nom : il est recalculé à chaque changement de nom.
     *
     * @throws \DomainException si un autre projet porte déjà ce nom
     */
    private function applyName(Project $project, string $name): void
    {
        $name = trim($name);
        $existingProject = $this->projectRepository->findOneBy(['name' => $name]);

        if (null !== $existingProject && $existingProject !== $project) {
            throw new \DomainException(\sprintf('Un projet s\'appelle déjà « %s ».', $name));
        }

        $project->setName($name);
        $project->setSlug($this->generateUniqueSlug($project, $name));
    }

    /**
     * « Site vitrine Dupont » → site-vitrine-dupont, puis -2, -3… si le slug est déjà pris.
     */
    private function generateUniqueSlug(Project $project, string $name): string
    {
        $baseSlug = $this->slugger->slug($name)->lower()->toString();
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->isSlugTaken($project, $slug)) {
            $slug = \sprintf('%s-%d', $baseSlug, $suffix);
            ++$suffix;
        }

        return $slug;
    }

    private function isSlugTaken(Project $project, string $slug): bool
    {
        $existingProject = $this->projectRepository->findOneBy(['slug' => $slug]);

        return null !== $existingProject && $existingProject !== $project;
    }

    private function cleanSummary(?string $summary): ?string
    {
        $summary = trim((string) $summary);

        return '' === $summary ? null : $summary;
    }
}
