<?php

namespace App\Service;

use App\Dto\ProjectCategoryPayload;
use App\Entity\ProjectCategory;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ajout, modification, suppression et ordre des catégories de projet (page Paramètres).
 */
final class ProjectCategoryManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectCategoryRepository $projectCategoryRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly PositionMover $positionMover,
    ) {
    }

    /**
     * La nouvelle catégorie est ajoutée à la fin de la liste.
     *
     * @throws \DomainException si une catégorie porte déjà ce nom
     */
    public function create(ProjectCategoryPayload $payload): ProjectCategory
    {
        $name = trim((string) $payload->name);
        $this->assertNameIsFree($name, null);

        $category = new ProjectCategory($name, $this->projectCategoryRepository->getNextPosition());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    /**
     * @throws \DomainException si une autre catégorie porte déjà ce nom
     */
    public function update(ProjectCategory $category, ProjectCategoryPayload $payload): void
    {
        $name = trim((string) $payload->name);
        $this->assertNameIsFree($name, $category);

        $category->setName($name);
        $this->entityManager->flush();
    }

    /**
     * Les projets de cette catégorie n'en ont simplement plus (la base met category_id à NULL).
     *
     * @return int le nombre de projets qui n'ont plus de catégorie
     */
    public function delete(ProjectCategory $category): int
    {
        $projectCount = $this->projectRepository->count(['category' => $category]);

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return $projectCount;
    }

    /**
     * Monte ou descend la catégorie d'un cran dans la liste (déjà en haut ou en bas : rien ne change).
     *
     * @param string $direction SectionMovePayload::UP ou SectionMovePayload::DOWN
     */
    public function move(ProjectCategory $category, string $direction): void
    {
        if ($this->positionMover->move($this->projectCategoryRepository->findAllOrdered(), $category, $direction)) {
            $this->entityManager->flush();
        }
    }

    /**
     * Comme pour la base de données, « jeu » et « Jeu » sont considérés comme le même nom.
     */
    private function assertNameIsFree(string $name, ?ProjectCategory $category): void
    {
        $existing = $this->projectCategoryRepository->findOneBy(['name' => $name]);

        if (null !== $existing && $existing !== $category) {
            throw new \DomainException(\sprintf('La catégorie « %s » existe déjà.', $existing->getName()));
        }
    }
}
