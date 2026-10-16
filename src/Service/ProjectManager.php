<?php

namespace App\Service;

use App\Dto\ProjectPayload;
use App\Entity\Project;
use App\Entity\ProjectState;
use App\Enum\ProjectImage;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Création, modification et suppression des projets : nom unique, slug calculé à partir du nom,
 * images facultatives (logo, logo long, avatar : voir ProjectImage), statut, catégorie et favori.
 */
final class ProjectManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository,
        private readonly SluggerInterface $slugger,
        private readonly ProjectStorage $projectStorage,
        private readonly ProjectStateRepository $projectStateRepository,
        private readonly ProjectCategoryRepository $projectCategoryRepository,
    ) {
    }

    /**
     * @param array<string, UploadedFile> $images fichiers envoyés, rangés par nom de champ (ProjectImage : logo, logoWide, avatar)
     *
     * @throws \DomainException si un projet porte déjà ce nom, ou si le statut ou la catégorie n'existe pas
     */
    public function create(ProjectPayload $payload, array $images = []): Project
    {
        $project = new Project();
        $this->applyName($project, (string) $payload->name);
        $project->setSummary($this->cleanSummary($payload->summary));
        $project->setState($this->findState($payload->state));
        $this->applyCategory($project, $payload->category);
        $project->setRepositoryUrl($this->cleanUrl($payload->repositoryUrl));
        $project->setWebsiteUrl($this->cleanUrl($payload->websiteUrl));

        $this->entityManager->persist($project);
        // Le projet doit avoir un id avant d'enregistrer ses images (son dossier porte l'id)
        $this->entityManager->flush();

        if ([] !== $images) {
            foreach (ProjectImage::cases() as $image) {
                if (isset($images[$image->value])) {
                    $project->setImage($image, $this->projectStorage->store($project, $images[$image->value], $image->filePrefix()));
                }
            }

            $this->entityManager->flush();
        }

        return $project;
    }

    /**
     * Le slug suit le nom : s'il change, l'URL du projet change aussi.
     *
     * @param array<string, UploadedFile> $images        nouvelles images (remplacent les anciennes), rangées par nom de champ
     * @param list<ProjectImage>          $removedImages images à supprimer (cases « Supprimer … » cochées)
     *
     * @throws \DomainException si un autre projet porte déjà ce nom, ou si le statut ou la catégorie n'existe pas
     */
    public function update(Project $project, ProjectPayload $payload, array $images = [], array $removedImages = []): Project
    {
        $this->applyName($project, (string) $payload->name);
        $project->setSummary($this->cleanSummary($payload->summary));

        if (null !== $payload->state) {
            $project->setState($this->findState($payload->state));
        }

        // Le formulaire de modification envoie toujours la catégorie (vide = aucune)
        if (null !== $payload->category) {
            $this->applyCategory($project, $payload->category);
        }

        // Liens : envoyés par le formulaire (vide = retiré), pas par les cartes de la page Projets (inchangés)
        if (null !== $payload->repositoryUrl) {
            $project->setRepositoryUrl($this->cleanUrl($payload->repositoryUrl));
        }

        if (null !== $payload->websiteUrl) {
            $project->setWebsiteUrl($this->cleanUrl($payload->websiteUrl));
        }

        foreach (ProjectImage::cases() as $image) {
            if (isset($images[$image->value])) {
                $this->replaceImage($project, $image, $this->projectStorage->store($project, $images[$image->value], $image->filePrefix()));
            } elseif (\in_array($image, $removedImages, true)) {
                $this->replaceImage($project, $image, null);
            }
        }

        $this->entityManager->flush();

        return $project;
    }

    /**
     * Supprime le projet avec tout ce qu'il contient : sections, tâches, sous-tâches et fichiers
     * (en base : cascade des relations), puis son dossier de fichiers (logo, images, documents).
     * Le dossier n'est effacé qu'une fois la base à jour : si la suppression en base échoue, rien n'est perdu.
     */
    public function delete(Project $project): void
    {
        $projectId = $project->getId();

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        if (null !== $projectId) {
            $this->projectStorage->deleteProjectDirectory($projectId);
        }
    }

    public function setFavorite(Project $project, bool $favorite): void
    {
        $project->setFavorite($favorite);
        $this->entityManager->flush();
    }

    /**
     * @param string|null $categoryId identifiant envoyé par le formulaire, « » ou null pour aucune catégorie
     *
     * @throws \DomainException si la catégorie n'existe pas
     */
    private function applyCategory(Project $project, ?string $categoryId): void
    {
        if (null === $categoryId || '' === $categoryId) {
            $project->setCategory(null);

            return;
        }

        $category = $this->projectCategoryRepository->find((int) $categoryId);

        if (null === $category) {
            throw new \DomainException('Cette catégorie n\'existe pas.');
        }

        $project->setCategory($category);
    }

    /**
     * Statut demandé par le formulaire, ou le premier de la liste s'il n'en donne pas.
     *
     * @throws \DomainException si le statut n'existe pas (ou s'il n'y a aucun statut)
     */
    private function findState(?string $code): ProjectState
    {
        $state = null === $code
            ? $this->projectStateRepository->findFirst()
            : $this->projectStateRepository->findOneBy(['code' => $code]);

        return $state ?? throw new \DomainException(null === $code
            ? 'Aucun statut de projet n\'existe : ajoute-en un depuis la liste des projets (roue dentée › Statuts).'
            : 'Ce statut n\'existe pas.');
    }

    /**
     * Change une image et supprime l'ancien fichier du stockage.
     */
    /**
     * Supprime une image du projet (corbeille de la page du projet) : le fichier est effacé du stockage.
     */
    public function removeImage(Project $project, ProjectImage $image): void
    {
        $this->replaceImage($project, $image, null);
        $this->entityManager->flush();
    }

    private function replaceImage(Project $project, ProjectImage $image, ?string $newFileName): void
    {
        $oldFileName = $project->getImage($image);
        $project->setImage($image, $newFileName);

        if (null !== $oldFileName) {
            $this->projectStorage->delete($project, $oldFileName);
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

    private function cleanUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return '' === $url ? null : $url;
    }

    private function cleanSummary(?string $summary): ?string
    {
        $summary = trim((string) $summary);

        return '' === $summary ? null : $summary;
    }
}
