<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectStep>
 */
class ProjectStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectStep::class);
    }

    /**
     * @return list<ProjectStep>
     */
    public function findByProjectOrdered(Project $project): array
    {
        return $this->findBy(['project' => $project], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /**
     * Position à donner à une nouvelle tâche : à la fin de la liste du projet.
     */
    public function findNextPosition(Project $project): int
    {
        $maxPosition = $this->createQueryBuilder('step')
            ->select('MAX(step.position)')
            ->where('step.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $maxPosition ? 0 : (int) $maxPosition + 1;
    }
}
