<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une paire terme / définition d'un bloc définitions (table project_section_definition),
 * affichée dans un <dt> et un <dd>.
 * La définition peut contenir la mise en forme simple : **gras**, *italique*, `code`, [lien](https://…).
 */
#[ORM\Entity]
class ProjectSectionDefinition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'definitions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionBlock $block;

    #[ORM\Column]
    private int $position;

    #[ORM\Column(length: 255)]
    private string $term;

    #[ORM\Column(type: Types::TEXT)]
    private string $definition;

    public function __construct(ProjectSectionBlock $block, int $position, string $term, string $definition)
    {
        $this->block = $block;
        $this->position = $position;
        $this->term = $term;
        $this->definition = $definition;
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

    public function getTerm(): string
    {
        return $this->term;
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }
}
