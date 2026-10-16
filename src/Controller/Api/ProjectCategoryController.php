<?php

namespace App\Controller\Api;

use App\Dto\ProjectCategoryPayload;
use App\Dto\SectionMovePayload;
use App\Entity\ProjectCategory;
use App\Service\ProjectCategoryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des catégories de projet : /api/parametres/categories
 *
 * Utilisée par la modal « Catégories » de la page Projets (roue dentée, assets/js/app/project/ProjectSettings.js) :
 * la liste est mise à jour sans recharger la page, donc pas de message flash.
 */
#[Route('/api/parametres/categories', name: 'api_project_categories_', requirements: ['id' => '\d+'])]
final class ProjectCategoryController extends AbstractController
{
    public function __construct(
        private readonly ProjectCategoryManager $projectCategoryManager,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ProjectCategoryPayload $payload): JsonResponse
    {
        try {
            $category = $this->projectCategoryManager->create($payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeCategory($category), Response::HTTP_CREATED);
    }

    #[Route('/{id:category}', name: 'update', methods: ['PATCH'])]
    public function update(ProjectCategory $category, #[MapRequestPayload] ProjectCategoryPayload $payload): JsonResponse
    {
        try {
            $this->projectCategoryManager->update($category, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeCategory($category));
    }

    /**
     * Monte ou descend d'un cran dans la liste. Pas de message : le nouvel ordre se voit.
     */
    #[Route('/{id:category}/position', name: 'move', methods: ['PUT'])]
    public function move(ProjectCategory $category, #[MapRequestPayload] SectionMovePayload $payload): JsonResponse
    {
        $this->projectCategoryManager->move($category, (string) $payload->direction);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id:category}', name: 'delete', methods: ['DELETE'])]
    public function delete(ProjectCategory $category): JsonResponse
    {
        // Les projets de cette catégorie n'ont plus de catégorie (la modal l'a dit avant de confirmer)
        $this->projectCategoryManager->delete($category);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{id: int|null, name: string}
     */
    private function serializeCategory(ProjectCategory $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
        ];
    }
}
