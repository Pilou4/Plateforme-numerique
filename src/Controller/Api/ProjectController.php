<?php

namespace App\Controller\Api;

use App\Dto\ProjectFavoritePayload;
use App\Dto\ProjectPayload;
use App\Entity\Project;
use App\Enum\ProjectImage;
use App\Service\ProjectManager;
use App\Validator\ProjectAvatar;
use App\Validator\ProjectLogo;
use App\Validator\ProjectLogoWide;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des projets : /api/projet
 */
#[Route('/api/projet', name: 'api_projects_')]
final class ProjectController extends AbstractController
{
    /**
     * Le formulaire est envoyé en multipart/form-data (à cause des images) :
     * les champs texte sont lus par MapRequestPayload, les fichiers par MapUploadedFile.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] ProjectPayload $payload,
        ProjectManager $projectManager,
        #[MapUploadedFile(new ProjectLogo())] ?UploadedFile $logo = null,
        #[MapUploadedFile(new ProjectLogoWide())] ?UploadedFile $logoWide = null,
        #[MapUploadedFile(new ProjectAvatar())] ?UploadedFile $avatar = null,
    ): JsonResponse {
        try {
            $project = $projectManager->create($payload, $this->collectImages($logo, $logoWide, $avatar));
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeProject($project), Response::HTTP_CREATED);
    }

    /**
     * Modification d'un projet. En POST et non en PATCH : PHP ne lit pas
     * les formulaires multipart/form-data (avec fichier) envoyés en PATCH.
     * Les cases « Supprimer le logo / le logo long / l'avatar » arrivent en removeLogo, removeLogoWide, removeAvatar.
     * Utilisé par le formulaire (modal) et par les cartes de la page Projets (modification directe) :
     * la réponse contient aussi le HTML de la carte (card).
     */
    #[Route('/{slug:project}', name: 'update', requirements: ['slug' => '[a-z0-9-]+'], methods: ['POST'])]
    public function update(
        Project $project,
        Request $request,
        #[MapRequestPayload] ProjectPayload $payload,
        ProjectManager $projectManager,
        #[MapUploadedFile(new ProjectLogo())] ?UploadedFile $logo = null,
        #[MapUploadedFile(new ProjectLogoWide())] ?UploadedFile $logoWide = null,
        #[MapUploadedFile(new ProjectAvatar())] ?UploadedFile $avatar = null,
    ): JsonResponse {
        $removedImages = array_values(array_filter(
            ProjectImage::cases(),
            static fn (ProjectImage $image): bool => $request->request->getBoolean($image->removeField()),
        ));

        try {
            $projectManager->update($project, $payload, $this->collectImages($logo, $logoWide, $avatar), $removedImages);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // La carte du projet, déjà mise à jour : la page Projets la remplace sans se recharger
        return $this->json($this->serializeProject($project) + [
            'card' => $this->renderView('app/project/_project_card.html.twig', ['project' => $project]),
        ]);
    }

    /**
     * Suppression d'un projet et de tout son contenu. Le message de confirmation est un message flash :
     * il s'affiche en bandeau sur la page renvoyée (la liste des projets).
     */
    #[Route('/{slug:project}', name: 'delete', requirements: ['slug' => '[a-z0-9-]+'], methods: ['DELETE'])]
    public function delete(Project $project, ProjectManager $projectManager): JsonResponse
    {
        $name = $project->getName();
        $projectManager->delete($project);
        $this->addFlash('success', \sprintf('Le projet « %s » a été supprimé.', $name));

        return $this->json(['url' => $this->generateUrl('app_project')]);
    }

    /**
     * Ajout ou retrait des favoris (étoile de la carte) : PUT { "favorite": true }
     */
    #[Route('/{slug:project}/favori', name: 'favorite', requirements: ['slug' => '[a-z0-9-]+'], methods: ['PUT'])]
    public function favorite(
        Project $project,
        #[MapRequestPayload] ProjectFavoritePayload $payload,
        ProjectManager $projectManager,
    ): JsonResponse {
        $projectManager->setFavorite($project, (bool) $payload->favorite);

        return $this->json(['favorite' => $project->isFavorite()]);
    }

    /**
     * Les images envoyées, rangées par nom de champ (ProjectImage) ; les champs vides sont ignorés.
     *
     * @return array<string, UploadedFile>
     */
    private function collectImages(?UploadedFile $logo, ?UploadedFile $logoWide, ?UploadedFile $avatar): array
    {
        return array_filter([
            ProjectImage::Logo->value => $logo,
            ProjectImage::LogoWide->value => $logoWide,
            ProjectImage::Avatar->value => $avatar,
        ]);
    }

    /**
     * L'URL est renvoyée car le slug peut changer avec le nom.
     *
     * @return array{id: int|null, name: string|null, slug: string|null, url: string}
     */
    private function serializeProject(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'slug' => $project->getSlug(),
            'url' => $this->generateUrl('app_project_show', ['slug' => $project->getSlug()]),
        ];
    }
}
