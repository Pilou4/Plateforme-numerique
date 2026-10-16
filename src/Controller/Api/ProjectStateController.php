<?php

namespace App\Controller\Api;

use App\Dto\ProjectStatePayload;
use App\Dto\SectionMovePayload;
use App\Entity\ProjectState;
use App\Service\ProjectStateManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des statuts de projet : /api/parametres/statuts
 *
 * Utilisée par la modal « Statuts » de la page Projets (roue dentée, assets/js/app/project/ProjectSettings.js) :
 * la liste est mise à jour sans recharger la page, donc pas de message flash.
 * Une suppression refusée (statut utilisé, dernier statut) renvoie une erreur 422 avec la raison.
 */
#[Route('/api/parametres/statuts', name: 'api_project_states_', requirements: ['id' => '\d+'])]
final class ProjectStateController extends AbstractController
{
    public function __construct(
        private readonly ProjectStateManager $projectStateManager,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ProjectStatePayload $payload): JsonResponse
    {
        try {
            $state = $this->projectStateManager->create($payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeState($state), Response::HTTP_CREATED);
    }

    #[Route('/{id:state}', name: 'update', methods: ['PATCH'])]
    public function update(ProjectState $state, #[MapRequestPayload] ProjectStatePayload $payload): JsonResponse
    {
        try {
            $this->projectStateManager->update($state, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeState($state));
    }

    /**
     * Monte ou descend d'un cran dans la liste. Pas de message : le nouvel ordre se voit.
     */
    #[Route('/{id:state}/position', name: 'move', methods: ['PUT'])]
    public function move(ProjectState $state, #[MapRequestPayload] SectionMovePayload $payload): JsonResponse
    {
        $this->projectStateManager->move($state, (string) $payload->direction);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id:state}', name: 'delete', methods: ['DELETE'])]
    public function delete(ProjectState $state): JsonResponse
    {
        try {
            $this->projectStateManager->delete($state);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{id: int|null, code: string, label: string, pluralLabel: string, color: string}
     */
    private function serializeState(ProjectState $state): array
    {
        return [
            'id' => $state->getId(),
            'code' => $state->getCode(),
            'label' => $state->getLabel(),
            'pluralLabel' => $state->getPluralLabel(),
            'color' => $state->getColor()->value,
        ];
    }
}
