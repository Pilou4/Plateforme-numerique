<?php

namespace App\Controller\Api;

use App\Dto\GlossaryTermPayload;
use App\Entity\GlossaryTerm;
use App\Service\GlossaryTermManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API du glossaire : /api/documentation/glossaire
 *
 * Après chaque action réussie, la page du glossaire est rechargée par le JavaScript
 * (les termes sont classés par lettre côté serveur) : le message de confirmation
 * est donc enregistré en message flash, affiché en bandeau au rechargement.
 */
#[Route('/api/documentation/glossaire', name: 'api_glossary_terms_', requirements: ['id' => '\d+'])]
final class GlossaryTermController extends AbstractController
{
    public function __construct(
        private readonly GlossaryTermManager $glossaryTermManager,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] GlossaryTermPayload $payload): JsonResponse
    {
        try {
            $term = $this->glossaryTermManager->create($payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash('success', \sprintf('« %s » a été ajouté au glossaire.', $term->getTerm()));

        return $this->json($this->serializeTerm($term), Response::HTTP_CREATED);
    }

    #[Route('/{id:term}', name: 'update', methods: ['PATCH'])]
    public function update(GlossaryTerm $term, #[MapRequestPayload] GlossaryTermPayload $payload): JsonResponse
    {
        try {
            $this->glossaryTermManager->update($term, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash('success', \sprintf('« %s » a été modifié.', $term->getTerm()));

        return $this->json($this->serializeTerm($term));
    }

    #[Route('/{id:term}', name: 'delete', methods: ['DELETE'])]
    public function delete(GlossaryTerm $term): JsonResponse
    {
        $name = $term->getTerm();
        $this->glossaryTermManager->delete($term);

        $this->addFlash('success', \sprintf('« %s » a été supprimé du glossaire.', $name));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{id: int|null, term: string|null, definition: string|null}
     */
    private function serializeTerm(GlossaryTerm $term): array
    {
        return [
            'id' => $term->getId(),
            'term' => $term->getTerm(),
            'definition' => $term->getDefinition(),
        ];
    }
}
