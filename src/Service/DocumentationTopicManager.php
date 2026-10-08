<?php

namespace App\Service;

use App\Dto\DocumentationTopicPayload;
use App\Entity\DocumentationTopic;
use App\Repository\DocumentationTopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Ajout, modification et suppression des tutoriels de la documentation.
 * Le slug (adresse de la page) suit le titre, comme pour les projets.
 */
final class DocumentationTopicManager
{
    /**
     * Slugs déjà utilisés par d'autres pages : /app/documentation/glossaire
     */
    private const array RESERVED_SLUGS = ['glossaire'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentationTopicRepository $topicRepository,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function create(DocumentationTopicPayload $payload): DocumentationTopic
    {
        $topic = new DocumentationTopic();
        $this->apply($topic, $payload);
        // Un nouveau tutoriel va à la fin de la liste
        $topic->setPosition($this->topicRepository->count());

        $this->entityManager->persist($topic);
        $this->entityManager->flush();

        return $topic;
    }

    public function update(DocumentationTopic $topic, DocumentationTopicPayload $payload): void
    {
        $this->apply($topic, $payload);
        $this->entityManager->flush();
    }

    /**
     * Supprime le tutoriel et toutes ses sections.
     */
    public function delete(DocumentationTopic $topic): void
    {
        foreach ($topic->getSections() as $section) {
            $this->entityManager->remove($section);
        }

        $this->entityManager->remove($topic);
        $this->entityManager->flush();
    }

    private function apply(DocumentationTopic $topic, DocumentationTopicPayload $payload): void
    {
        $title = trim((string) $payload->title);
        $summary = trim((string) $payload->summary);

        $topic->setTitle($title);
        $topic->setSummary('' === $summary ? null : $summary);
        $topic->setSlug($this->generateUniqueSlug($topic, $title));
    }

    /**
     * « Git et GitHub » → git-et-github, puis -2, -3… si le slug est déjà pris.
     */
    private function generateUniqueSlug(DocumentationTopic $topic, string $title): string
    {
        $baseSlug = $this->slugger->slug($title)->lower()->toString();
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->isSlugTaken($topic, $slug)) {
            $slug = \sprintf('%s-%d', $baseSlug, $suffix);
            ++$suffix;
        }

        return $slug;
    }

    private function isSlugTaken(DocumentationTopic $topic, string $slug): bool
    {
        if (\in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        $existing = $this->topicRepository->findOneBy(['slug' => $slug]);

        return null !== $existing && $existing !== $topic;
    }
}
