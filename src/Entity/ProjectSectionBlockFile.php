<?php

namespace App\Entity;

use App\Repository\ProjectSectionBlockFileRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lien entre un bloc images ou documents et un fichier du projet (table project_section_block_file).
 *
 * Le fichier reste rangé dans la page Fichiers (project_file) : le bloc ne fait que l'afficher,
 * avec sa position dans le bloc et une légende propre à cet endroit.
 * - supprimer le bloc supprime le lien, jamais le fichier ;
 * - supprimer le fichier supprime ses liens (ON DELETE CASCADE) : il disparaît des sections.
 */
#[ORM\Entity(repositoryClass: ProjectSectionBlockFileRepository::class)]
#[ORM\UniqueConstraint(name: 'project_section_block_file_block_file', columns: ['block_id', 'file_id'])]
class ProjectSectionBlockFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionBlock $block;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectFile $file;

    #[ORM\Column]
    private int $position;

    /**
     * Légende affichée sous l'image (ou à côté du document), facultative.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $caption;

    public function __construct(ProjectSectionBlock $block, ProjectFile $file, int $position, ?string $caption = null)
    {
        $this->block = $block;
        $this->file = $file;
        $this->position = $position;
        $this->caption = $caption;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlock(): ProjectSectionBlock
    {
        return $this->block;
    }

    public function getFile(): ProjectFile
    {
        return $this->file;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }
}
