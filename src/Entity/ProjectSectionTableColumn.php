<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Une colonne d'un bloc tableau (table project_section_table_column) : son en-tête est affiché dans un <th>.
 * Les cases de la colonne (project_section_table_cell) pointent vers elle : supprimer la colonne
 * supprime ses cases dans toutes les lignes (ON DELETE CASCADE).
 */
#[ORM\Entity]
class ProjectSectionTableColumn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tableColumns')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionBlock $block;

    #[ORM\Column]
    private int $position;

    /**
     * Texte de l'en-tête (peut être vide).
     */
    #[ORM\Column(length: 255)]
    private string $label;

    public function __construct(ProjectSectionBlock $block, int $position, string $label)
    {
        $this->block = $block;
        $this->position = $position;
        $this->label = $label;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlock(): ProjectSectionBlock
    {
        return $this->block;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
