<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une case d'un bloc tableau (table project_section_table_cell), affichée dans un <td>.
 * Elle est à la croisée d'une ligne et d'une colonne : une seule case par couple ligne + colonne.
 * Le texte peut contenir la mise en forme simple : **gras**, *italique*, `code`, [lien](https://…).
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'project_section_table_cell_row_column', columns: ['row_id', 'column_id'])]
class ProjectSectionTableCell
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cells')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionTableRow $row;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'column_id', nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionTableColumn $column;

    #[ORM\Column(type: Types::TEXT)]
    private string $text;

    public function __construct(ProjectSectionTableRow $row, ProjectSectionTableColumn $column, string $text)
    {
        $this->row = $row;
        $this->column = $column;
        $this->text = $text;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRow(): ProjectSectionTableRow
    {
        return $this->row;
    }

    public function getColumn(): ProjectSectionTableColumn
    {
        return $this->column;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
