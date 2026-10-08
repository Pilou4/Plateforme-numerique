<?php

namespace App\Repository;

use App\Entity\ProjectStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectStatus>
 */
class ProjectStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectStatus::class);
    }

    /**
     * Les statuts dans l'ordre d'avancement (listes déroulantes, filtres).
     *
     * @return list<ProjectStatus>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC']);
    }

    /**
     * @throws \LogicException si le statut n'existe pas en base (migration pas lancée)
     */
    public function getByCode(string $code): ProjectStatus
    {
        return $this->findOneBy(['code' => $code])
            ?? throw new \LogicException(\sprintf('Le statut « %s » n\'existe pas dans la table project_status : lance les migrations.', $code));
    }
}
