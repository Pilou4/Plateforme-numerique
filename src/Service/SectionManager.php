<?php

namespace App\Service;

use App\Dto\SectionMovePayload;
use App\Dto\SectionPayload;
use App\Entity\ContentSectionInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ajout, modification, suppression et ordre des sections d'une page
 * (tutoriel de la documentation ou présentation d'un projet).
 *
 * Le contrôleur crée la section du bon type (DocumentationSection ou ProjectSection)
 * et passe la liste des sections de la page : le reste est identique pour les deux.
 * Les positions sont toujours 0, 1, 2… dans l'ordre d'affichage.
 */
final class SectionManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * La section doit déjà être rattachée à sa page (tutoriel ou projet).
     *
     * @param Collection<int, ContentSectionInterface> $sections toutes les sections de la page
     */
    public function add(Collection $sections, ContentSectionInterface $section, SectionPayload $payload): void
    {
        $this->apply($section, $payload);
        // Une nouvelle section va à la fin de la page : après toutes les autres
        $otherSections = array_filter($sections->toArray(), static fn (ContentSectionInterface $item): bool => $item !== $section);
        $section->setPosition(\count($otherSections));

        $this->entityManager->persist($section);
        $this->entityManager->flush();
    }

    public function update(ContentSectionInterface $section, SectionPayload $payload): void
    {
        $this->apply($section, $payload);
        $this->entityManager->flush();
    }

    /**
     * @param Collection<int, ContentSectionInterface> $sections toutes les sections de la page
     */
    public function delete(Collection $sections, ContentSectionInterface $section): void
    {
        $sections->removeElement($section);
        $this->entityManager->remove($section);
        $this->renumber($this->sortByPosition($sections->toArray()));

        $this->entityManager->flush();
    }

    /**
     * Échange la section avec sa voisine du dessus ou du dessous.
     * Sans voisine (déjà en haut ou en bas), rien ne change.
     *
     * @param Collection<int, ContentSectionInterface> $sections toutes les sections de la page
     */
    public function move(Collection $sections, ContentSectionInterface $section, string $direction): void
    {
        $ordered = $this->sortByPosition($sections->toArray());
        $index = array_search($section, $ordered, true);
        $targetIndex = SectionMovePayload::UP === $direction ? $index - 1 : $index + 1;

        if (false === $index || !isset($ordered[$targetIndex])) {
            return;
        }

        [$ordered[$index], $ordered[$targetIndex]] = [$ordered[$targetIndex], $ordered[$index]];
        $this->renumber($ordered);

        $this->entityManager->flush();
    }

    private function apply(ContentSectionInterface $section, SectionPayload $payload): void
    {
        $section->setTitle(trim((string) $payload->title));
        $section->setContent(trim((string) $payload->content));
    }

    /**
     * @param list<ContentSectionInterface> $sections
     *
     * @return list<ContentSectionInterface>
     */
    private function sortByPosition(array $sections): array
    {
        usort($sections, static fn (ContentSectionInterface $a, ContentSectionInterface $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0));

        return $sections;
    }

    /**
     * @param list<ContentSectionInterface> $ordered
     */
    private function renumber(array $ordered): void
    {
        foreach (array_values($ordered) as $position => $section) {
            $section->setPosition($position);
        }
    }
}
