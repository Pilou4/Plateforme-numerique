<?php

namespace App\Controller\Api;

use App\Dto\SectionMovePayload;
use App\Dto\SectionPayload;
use App\Entity\Project;
use App\Entity\ProjectSection;
use App\Service\SectionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des sections de la présentation d'un projet : /api/projet/{slug}/sections
 *
 * Comme pour les tutoriels : après chaque action, le JavaScript recharge la page
 * et le message de confirmation s'affiche en bandeau (message flash).
 */
#[Route('/api/projet/{slug:project}/sections', name: 'api_project_sections_', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
final class ProjectSectionController extends AbstractController
{
    public function __construct(
        private readonly SectionManager $sectionManager,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Project $project, #[MapRequestPayload] SectionPayload $payload): JsonResponse
    {
        $section = new ProjectSection();
        $project->addSection($section);
        $this->sectionManager->add($project->getSections(), $section, $payload);
        $this->addFlash('success', \sprintf('La section « %s » a été ajoutée.', $section->getTitle()));

        return $this->json(['url' => $this->getProjectUrl($project)], Response::HTTP_CREATED);
    }

    #[Route('/{id:section}', name: 'update', methods: ['PATCH'])]
    public function update(Project $project, ProjectSection $section, #[MapRequestPayload] SectionPayload $payload): JsonResponse
    {
        $this->assertBelongsToProject($project, $section);
        $this->sectionManager->update($section, $payload);
        $this->addFlash('success', \sprintf('La section « %s » a été modifiée.', $section->getTitle()));

        return $this->json(['url' => $this->getProjectUrl($project)]);
    }

    #[Route('/{id:section}', name: 'delete', methods: ['DELETE'])]
    public function delete(Project $project, ProjectSection $section): JsonResponse
    {
        $this->assertBelongsToProject($project, $section);
        $title = $section->getTitle();
        $this->sectionManager->delete($project->getSections(), $section);
        $this->addFlash('success', \sprintf('La section « %s » a été supprimée.', $title));

        return $this->json(['url' => $this->getProjectUrl($project)]);
    }

    /**
     * Monte ou descend la section d'un cran. Pas de message : le nouvel ordre se voit.
     */
    #[Route('/{id:section}/position', name: 'move', methods: ['PUT'])]
    public function move(Project $project, ProjectSection $section, #[MapRequestPayload] SectionMovePayload $payload): JsonResponse
    {
        $this->assertBelongsToProject($project, $section);
        $this->sectionManager->move($project->getSections(), $section, $payload->direction);

        return $this->json(['url' => $this->getProjectUrl($project)]);
    }

    private function assertBelongsToProject(Project $project, ProjectSection $section): void
    {
        if ($section->getProject() !== $project) {
            throw new NotFoundHttpException('Cette section n\'appartient pas à ce projet.');
        }
    }

    private function getProjectUrl(Project $project): string
    {
        return $this->generateUrl('app_project_show', ['slug' => $project->getSlug()]);
    }
}
