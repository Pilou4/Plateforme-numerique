<?php

namespace App\Repository;

use App\Entity\ProjectFile;
use App\Entity\ProjectSectionBlockFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectSectionBlockFile>
 */
class ProjectSectionBlockFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectSectionBlockFile::class);
    }

    /**
     * Les titres des sections qui affichent ce fichier (sans doublon, dans l'ordre de la présentation) :
     * la page Fichiers les cite avant de supprimer le fichier.
     *
     * @return list<string>
     */
    public function findSectionTitlesUsingFile(ProjectFile $file): array
    {
        $rows = $this->createQueryBuilder('link')
            ->select('DISTINCT section.title, section.position')
            ->join('link.block', 'block')
            ->join('block.section', 'section')
            ->where('link.file = :file')
            ->setParameter('file', $file)
            ->orderBy('section.position', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(static fn (array $row): string => (string) $row['title'], $rows));
    }
}
