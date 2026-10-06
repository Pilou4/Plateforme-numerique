<?php

namespace App\Controller\Api;

use App\Dto\ProjectStepOrderPayload;
use App\Dto\ProjectStepPayload;
use App\Entity\Project;
use App\Entity\ProjectStep;
use App\Entity\ProjectSubStep;
use App\Service\ProjectSubStepManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des sous-tâches : /api/projet/{slug}/taches/{stepId}/sous-taches
 * Chaque action renvoie la tâche parente complète (avec ses sous-tâches),
 * car son statut et son temps total peuvent changer.
 */
#[Route(
    '/api/projet/{slug:project}/taches/{stepId}/sous-taches',
    name: 'api_project_sub_steps_',
    requirements: ['slug' => '[a-z0-9-]+', 'stepId' => '\d+'],
)]
final class ProjectSubStepController extends AbstractController
{
    private const array READ_CONTEXT = ['groups' => [ProjectStep::GROUP_READ]];

    public function __construct(
        private readonly ProjectSubStepManager $projectSubStepManager,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Project $project,
        #[MapEntity(id: 'stepId')] ProjectStep $step,
        #[MapRequestPayload(validationGroups: ['Default', ProjectStepPayload::GROUP_CREATE])] ProjectStepPayload $payload,
    ): JsonResponse {
        $this->assertStepBelongsToProject($step, $project);

        try {
            $this->projectSubStepManager->create($step, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($step, Response::HTTP_CREATED, context: self::READ_CONTEXT);
    }

    #[Route('/ordre', name: 'reorder', methods: ['PUT'])]
    public function reorder(
        Project $project,
        #[MapEntity(id: 'stepId')] ProjectStep $step,
        #[MapRequestPayload] ProjectStepOrderPayload $payload,
    ): JsonResponse {
        $this->assertStepBelongsToProject($step, $project);

        try {
            $this->projectSubStepManager->reorder($step, $payload->ids);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($step, context: self::READ_CONTEXT);
    }

    #[Route('/{id:subStep}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(
        Project $project,
        int $stepId,
        ProjectSubStep $subStep,
        #[MapRequestPayload] ProjectStepPayload $payload,
    ): JsonResponse {
        $this->assertSubStepBelongsTo($subStep, $stepId, $project);

        try {
            $this->projectSubStepManager->update($subStep, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($subStep->getStep(), context: self::READ_CONTEXT);
    }

    #[Route('/{id:subStep}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Project $project, int $stepId, ProjectSubStep $subStep): JsonResponse
    {
        $this->assertSubStepBelongsTo($subStep, $stepId, $project);

        $step = $subStep->getStep();
        $this->projectSubStepManager->delete($subStep);

        return $this->json($step, context: self::READ_CONTEXT);
    }

    private function assertStepBelongsToProject(ProjectStep $step, Project $project): void
    {
        if ($step->getProject() !== $project) {
            throw new NotFoundHttpException('Cette tâche n\'appartient pas à ce projet.');
        }
    }

    /**
     * L'URL contient le projet et la tâche : la sous-tâche doit bien leur appartenir.
     */
    private function assertSubStepBelongsTo(ProjectSubStep $subStep, int $stepId, Project $project): void
    {
        $step = $subStep->getStep();

        if (null === $step || $step->getId() !== $stepId || $step->getProject() !== $project) {
            throw new NotFoundHttpException('Cette sous-tâche n\'appartient pas à cette tâche.');
        }
    }
}
