<?php

namespace App\Controller\Api;

use App\Dto\DocumentationTopicPayload;
use App\Dto\SectionMovePayload;
use App\Dto\SectionPayload;
use App\Entity\DocumentationSection;
use App\Entity\DocumentationTopic;
use App\Service\DocumentationTopicManager;
use App\Service\SectionManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des tutoriels et de leurs sections : /api/documentation/tutoriels
 *
 * Les pages sont affichées par Twig : après chaque action réussie, le JavaScript va à l'adresse
 * renvoyée (« url ») et le message de confirmation s'affiche en bandeau (message flash).
 */
#[Route('/api/documentation/tutoriels', name: 'api_documentation_topics_', requirements: ['id' => '\d+', 'sectionId' => '\d+'])]
final class DocumentationTopicController extends AbstractController
{
    public function __construct(
        private readonly DocumentationTopicManager $topicManager,
        private readonly SectionManager $sectionManager,
    ) {
    }

    /* ------------------------------------------------------------------
       Tutoriels
       ------------------------------------------------------------------ */

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] DocumentationTopicPayload $payload): JsonResponse
    {
        $topic = $this->topicManager->create($payload);
        $this->addFlash('success', \sprintf('Le tutoriel « %s » a été créé.', $topic->getTitle()));

        return $this->json(['url' => $this->getTopicUrl($topic)], Response::HTTP_CREATED);
    }

    /**
     * L'adresse est renvoyée car le slug suit le titre.
     */
    #[Route('/{id:topic}', name: 'update', methods: ['PATCH'])]
    public function update(DocumentationTopic $topic, #[MapRequestPayload] DocumentationTopicPayload $payload): JsonResponse
    {
        $this->topicManager->update($topic, $payload);
        $this->addFlash('success', 'Le tutoriel a été modifié.');

        return $this->json(['url' => $this->getTopicUrl($topic)]);
    }

    #[Route('/{id:topic}', name: 'delete', methods: ['DELETE'])]
    public function delete(DocumentationTopic $topic): JsonResponse
    {
        $title = $topic->getTitle();
        $this->topicManager->delete($topic);
        $this->addFlash('success', \sprintf('Le tutoriel « %s » a été supprimé.', $title));

        return $this->json(['url' => $this->generateUrl('app_documentation')]);
    }

    /* ------------------------------------------------------------------
       Sections d'un tutoriel
       ------------------------------------------------------------------ */

    #[Route('/{id:topic}/sections', name: 'section_create', methods: ['POST'])]
    public function createSection(DocumentationTopic $topic, #[MapRequestPayload] SectionPayload $payload): JsonResponse
    {
        $section = new DocumentationSection();
        $topic->addSection($section);
        $this->sectionManager->add($topic->getSections(), $section, $payload);
        $this->addFlash('success', \sprintf('La section « %s » a été ajoutée.', $section->getTitle()));

        return $this->json(['url' => $this->getTopicUrl($topic)], Response::HTTP_CREATED);
    }

    #[Route('/{id:topic}/sections/{sectionId}', name: 'section_update', methods: ['PATCH'])]
    public function updateSection(
        DocumentationTopic $topic,
        #[MapEntity(id: 'sectionId')] DocumentationSection $section,
        #[MapRequestPayload] SectionPayload $payload,
    ): JsonResponse {
        $this->assertBelongsToTopic($topic, $section);
        $this->sectionManager->update($section, $payload);
        $this->addFlash('success', \sprintf('La section « %s » a été modifiée.', $section->getTitle()));

        return $this->json(['url' => $this->getTopicUrl($topic)]);
    }

    #[Route('/{id:topic}/sections/{sectionId}', name: 'section_delete', methods: ['DELETE'])]
    public function deleteSection(DocumentationTopic $topic, #[MapEntity(id: 'sectionId')] DocumentationSection $section): JsonResponse
    {
        $this->assertBelongsToTopic($topic, $section);
        $title = $section->getTitle();
        $this->sectionManager->delete($topic->getSections(), $section);
        $this->addFlash('success', \sprintf('La section « %s » a été supprimée.', $title));

        return $this->json(['url' => $this->getTopicUrl($topic)]);
    }

    /**
     * Monte ou descend la section d'un cran. Pas de message : le nouvel ordre se voit.
     */
    #[Route('/{id:topic}/sections/{sectionId}/position', name: 'section_move', methods: ['PUT'])]
    public function moveSection(
        DocumentationTopic $topic,
        #[MapEntity(id: 'sectionId')] DocumentationSection $section,
        #[MapRequestPayload] SectionMovePayload $payload,
    ): JsonResponse {
        $this->assertBelongsToTopic($topic, $section);
        $this->sectionManager->move($topic->getSections(), $section, $payload->direction);

        return $this->json(['url' => $this->getTopicUrl($topic)]);
    }

    private function assertBelongsToTopic(DocumentationTopic $topic, DocumentationSection $section): void
    {
        if ($section->getTopic() !== $topic) {
            throw new NotFoundHttpException('Cette section n\'appartient pas à ce tutoriel.');
        }
    }

    private function getTopicUrl(DocumentationTopic $topic): string
    {
        return $this->generateUrl('app_documentation_show', ['slug' => $topic->getSlug()]);
    }
}
