<?php

namespace App\Service;

use App\Dto\ProjectSectionPayload;
use App\Dto\SectionMovePayload;
use App\Entity\Project;
use App\Entity\ProjectSection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ajout, modification, suppression et ordre des sections de la présentation d'un projet.
 *
 * Une section est enregistrée en entier à chaque fois : son titre et tous ses blocs, dans l'ordre
 * (ProjectSectionBlockBuilder vérifie et crée les blocs). Les positions des sections sont
 * toujours 0, 1, 2… dans l'ordre d'affichage.
 */
final class ProjectSectionManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectSectionBlockBuilder $blockBuilder,
    ) {
    }

    /**
     * La nouvelle section va à la fin de la présentation.
     *
     * @throws \DomainException si un bloc est vide ou mal rempli
     */
    public function create(Project $project, ProjectSectionPayload $payload): ProjectSection
    {
        $section = (new ProjectSection())->setTitle(trim((string) $payload->title));
        // Le projet doit être connu avant les blocs : les fichiers doivent appartenir à ce projet
        $section->setProject($project);
        $this->blockBuilder->replaceBlocks($section, $payload->blocks);

        $section->setPosition($project->getSections()->count());
        $project->addSection($section);

        $this->entityManager->persist($section);
        $this->entityManager->flush();

        return $section;
    }

    /**
     * @throws \DomainException si un bloc est vide ou mal rempli (la section n'est alors pas modifiée)
     */
    public function update(ProjectSection $section, ProjectSectionPayload $payload): void
    {
        $this->blockBuilder->replaceBlocks($section, $payload->blocks);
        $section->setTitle(trim((string) $payload->title));

        $this->entityManager->flush();
    }

    public function delete(Project $project, ProjectSection $section): void
    {
        $project->removeSection($section);
        $this->entityManager->remove($section);
        $this->renumber($this->sortByPosition($project->getSections()->toArray()));

        $this->entityManager->flush();
    }

    /**
     * Échange la section avec sa voisine du dessus ou du dessous.
     * Sans voisine (déjà en haut ou en bas), rien ne change.
     */
    public function move(Project $project, ProjectSection $section, string $direction): void
    {
        $ordered = $this->sortByPosition($project->getSections()->toArray());
        $index = array_search($section, $ordered, true);
        $targetIndex = SectionMovePayload::UP === $direction ? $index - 1 : $index + 1;

        if (false === $index || !isset($ordered[$targetIndex])) {
            return;
        }

        [$ordered[$index], $ordered[$targetIndex]] = [$ordered[$targetIndex], $ordered[$index]];
        $this->renumber($ordered);

        $this->entityManager->flush();
    }

    /**
     * @param array<ProjectSection> $sections
     *
     * @return list<ProjectSection>
     */
    private function sortByPosition(array $sections): array
    {
        usort($sections, static fn (ProjectSection $a, ProjectSection $b): int => $a->getPosition() <=> $b->getPosition());

        return $sections;
    }

    /**
     * @param list<ProjectSection> $ordered
     */
    private function renumber(array $ordered): void
    {
        foreach ($ordered as $position => $section) {
            $section->setPosition($position);
        }
    }
}
