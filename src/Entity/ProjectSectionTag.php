<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Un tag d'un bloc tags (table project_section_tag), affiché en pastille.
 * Créé par ProjectSectionBlock (add…) : il ne change plus ensuite, la section est réenregistrée en entier.
 */
#[ORM\Entity]
class ProjectSectionTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionBlock $block;

    #[ORM\Column]
    private int $position;

    #[ORM\Column(length: 100)]
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
