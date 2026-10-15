<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne d'un bloc tableau (table project_section_table_row), affichée dans un <tr>.
 * Elle contient une case par colonne (project_section_table_cell).
 */
#[ORM\Entity]
class ProjectSectionTableRow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tableRows')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionBlock $block;

    #[ORM\Column]
    private int $position;

    /**
     * @var Collection<int, ProjectSectionTableCell>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionTableCell::class, mappedBy: 'row', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $cells;

    public function __construct(ProjectSectionBlock $block, int $position)
    {
        $this->block = $block;
        $this->position = $position;
        $this->cells = new ArrayCollection();
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

    /**
     * @return Collection<int, ProjectSectionTableCell>
     */
    public function getCells(): Collection
    {
        return $this->cells;
    }

    public function addCell(ProjectSectionTableColumn $column, string $text): static
    {
        $this->cells->add(new ProjectSectionTableCell($this, $column, $text));

        return $this;
    }

    /**
     * Les cases dans l'ordre des colonnes (pour l'affichage).
     *
     * @return list<ProjectSectionTableCell>
     */
    public function getOrderedCells(): array
    {
        $cells = $this->cells->toArray();
        usort($cells, static fn (ProjectSectionTableCell $a, ProjectSectionTableCell $b): int => $a->getColumn()->getPosition() <=> $b->getColumn()->getPosition());

        return $cells;
    }
}
