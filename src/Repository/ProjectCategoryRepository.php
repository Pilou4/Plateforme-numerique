<?php

namespace App\Repository;

use App\Entity\ProjectCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectCategory>
 */
class ProjectCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectCategory::class);
    }

    /**
     * Les catégories dans l'ordre de la liste déroulante.
     *
     * @return list<ProjectCategory>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC']);
    }

    /**
     * Position d'une nouvelle catégorie : à la fin de la liste.
     */
    public function getNextPosition(): int
    {
        $max = $this->createQueryBuilder('category')
            ->select('MAX(category.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return null === $max ? 0 : (int) $max + 1;
    }
}
