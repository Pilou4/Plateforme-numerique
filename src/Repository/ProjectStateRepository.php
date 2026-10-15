<?php

namespace App\Repository;

use App\Entity\ProjectState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectState>
 */
class ProjectStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectState::class);
    }

    /**
     * Les états dans l'ordre d'avancement (filtres, liste déroulante).
     *
     * @return list<ProjectState>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC']);
    }

    /**
     * Le premier statut de la liste : celui d'un nouveau projet quand le formulaire n'en donne pas.
     */
    public function findFirst(): ?ProjectState
    {
        return $this->findOneBy([], ['position' => 'ASC']);
    }

    /**
     * Position d'un nouveau statut : à la fin de la liste.
     */
    public function getNextPosition(): int
    {
        $max = $this->createQueryBuilder('state')
            ->select('MAX(state.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return null === $max ? 0 : (int) $max + 1;
    }

    /**
     * @throws \LogicException si l'état n'existe pas en base (migration pas lancée)
     */
    public function getByCode(string $code): ProjectState
    {
        return $this->findOneBy(['code' => $code])
            ?? throw new \LogicException(\sprintf('L\'état « %s » n\'existe pas dans la table project_state : lance les migrations.', $code));
    }
}
