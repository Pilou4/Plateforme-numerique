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
 * Après chaque action réussie, la page Paramètres est rechargée par le JavaScript :
 * le message de confirmation est enregistré en message flash, affiché en bandeau au rechargement.
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

        $this->addFlash('success', \sprintf('La catégorie « %s » a été ajoutée.', $category->getName()));

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

        $this->addFlash('success', \sprintf('La catégorie « %s » a été modifiée.', $category->getName()));

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
        $name = $category->getName();
        $projectCount = $this->projectCategoryManager->delete($category);

        $this->addFlash('success', match ($projectCount) {
            0 => \sprintf('La catégorie « %s » a été supprimée.', $name),
            1 => \sprintf('La catégorie « %s » a été supprimée : 1 projet n\'a plus de catégorie.', $name),
            default => \sprintf('La catégorie « %s » a été supprimée : %d projets n\'ont plus de catégorie.', $name, $projectCount),
        });

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
