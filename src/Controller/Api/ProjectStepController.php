<?php

namespace App\Controller\Api;

use App\Dto\ProjectStepOrderPayload;
use App\Dto\ProjectStepPayload;
use App\Entity\Project;
use App\Entity\ProjectStep;
use App\Repository\ProjectStepRepository;
use App\Service\ProjectStepManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des tâches d'un projet : /api/projet/{slug}/taches
 */
#[Route('/api/projet/{slug:project}/taches', name: 'api_project_steps_', requirements: ['slug' => '[a-z0-9-]+'])]
final class ProjectStepController extends AbstractController
{
    private const array READ_CONTEXT = ['groups' => [ProjectStep::GROUP_READ]];

    public function __construct(
        private readonly ProjectStepManager $projectStepManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Project $project, ProjectStepRepository $projectStepRepository): JsonResponse
    {
        return $this->json($projectStepRepository->findByProjectOrdered($project), context: self::READ_CONTEXT);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Project $project,
        #[MapRequestPayload(validationGroups: ['Default', ProjectStepPayload::GROUP_CREATE])] ProjectStepPayload $payload,
    ): JsonResponse {
        try {
            $step = $this->projectStepManager->create($project, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($step, Response::HTTP_CREATED, context: self::READ_CONTEXT);
    }

    #[Route('/ordre', name: 'reorder', methods: ['PUT'])]
    public function reorder(Project $project, #[MapRequestPayload] ProjectStepOrderPayload $payload): JsonResponse
    {
        try {
            $this->projectStepManager->reorder($project, $payload->ids);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id:step}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(Project $project, ProjectStep $step, #[MapRequestPayload] ProjectStepPayload $payload): JsonResponse
    {
        $this->assertBelongsToProject($step, $project);

        try {
            $this->projectStepManager->update($step, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($step, context: self::READ_CONTEXT);
    }

    #[Route('/{id:step}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Project $project, ProjectStep $step): JsonResponse
    {
        $this->assertBelongsToProject($step, $project);
        $this->projectStepManager->delete($step);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * L'URL contient le projet : la tâche doit bien lui appartenir.
     */
    private function assertBelongsToProject(ProjectStep $step, Project $project): void
    {
        if ($step->getProject() !== $project) {
            throw new NotFoundHttpException('Cette tâche n\'appartient pas à ce projet.');
        }
    }
}
