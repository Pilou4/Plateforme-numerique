<?php

namespace App\Controller\Api;

use App\Dto\ProjectSectionPayload;
use App\Dto\SectionMovePayload;
use App\Entity\Project;
use App\Entity\ProjectSection;
use App\Service\ProjectSectionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des sections de la présentation d'un projet : /api/projet/{slug}/sections
 *
 * L'éditeur envoie la section entière (titre + blocs, voir ProjectSectionPayload).
 * Après chaque action, le JavaScript recharge la page et le message de confirmation
 * s'affiche en bandeau (message flash). Un bloc mal rempli renvoie une erreur 422 qui dit lequel.
 */
#[Route('/api/projet/{slug:project}/sections', name: 'api_project_sections_', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
final class ProjectSectionController extends AbstractController
{
    public function __construct(
        private readonly ProjectSectionManager $projectSectionManager,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Project $project, #[MapRequestPayload] ProjectSectionPayload $payload): JsonResponse
    {
        try {
            $section = $this->projectSectionManager->create($project, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash('success', \sprintf('La section « %s » a été ajoutée.', $section->getTitle()));

        return $this->json(['url' => $this->getSectionUrl($project, $section)], Response::HTTP_CREATED);
    }

    #[Route('/{id:section}', name: 'update', methods: ['PUT'])]
    public function update(Project $project, ProjectSection $section, #[MapRequestPayload] ProjectSectionPayload $payload): JsonResponse
    {
        $this->assertBelongsToProject($project, $section);

        try {
            $this->projectSectionManager->update($section, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash('success', \sprintf('La section « %s » a été modifiée.', $section->getTitle()));

        return $this->json(['url' => $this->getSectionUrl($project, $section)]);
    }

    #[Route('/{id:section}', name: 'delete', methods: ['DELETE'])]
    public function delete(Project $project, ProjectSection $section): JsonResponse
    {
        $this->assertBelongsToProject($project, $section);
        $title = $section->getTitle();
        $this->projectSectionManager->delete($project, $section);
        $this->addFlash('success', \sprintf('La section « %s » a été supprimée.', $title));

        return $this->json(['url' => $this->generateUrl('app_project_show', ['slug' => $project->getSlug()])]);
    }

    /**
     * Monte ou descend la section d'un cran. Pas de message : le nouvel ordre se voit.
     */
    #[Route('/{id:section}/position', name: 'move', methods: ['PUT'])]
    public function move(Project $project, ProjectSection $section, #[MapRequestPayload] SectionMovePayload $payload): JsonResponse
    {
        $this->assertBelongsToProject($project, $section);
        $this->projectSectionManager->move($project, $section, (string) $payload->direction);

        return $this->json(['url' => $this->getSectionUrl($project, $section)]);
    }

    private function assertBelongsToProject(Project $project, ProjectSection $section): void
    {
        if ($section->getProject() !== $project) {
            throw new NotFoundHttpException('Cette section n\'appartient pas à ce projet.');
        }
    }

    /**
     * La page du projet, positionnée sur la section (ancre #section-{id}).
     */
    private function getSectionUrl(Project $project, ProjectSection $section): string
    {
        return $this->generateUrl('app_project_show', ['slug' => $project->getSlug(), '_fragment' => 'section-'.$section->getId()]);
    }
}
