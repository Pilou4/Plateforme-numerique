<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectFile>
 */
class ProjectFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectFile::class);
    }

    /**
     * Fichiers d'un projet, du plus récent au plus ancien.
     *
     * @return list<ProjectFile>
     */
    public function findByProjectNewestFirst(Project $project): array
    {
        return $this->findBy(['project' => $project], ['createdAt' => 'DESC', 'id' => 'DESC']);
    }
}
