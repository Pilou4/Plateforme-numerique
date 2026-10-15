<?php

namespace App\Entity;

use App\Enum\ProjectSectionTagColor;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un tag d'un bloc tags (table project_section_tag), affiché en pastille de la couleur choisie.
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

    #[ORM\Column(length: 20, enumType: ProjectSectionTagColor::class, options: ['default' => 'bleu'])]
    private ProjectSectionTagColor $color;

    public function __construct(ProjectSectionBlock $block, int $position, string $label, ?ProjectSectionTagColor $color = null)
    {
        $this->block = $block;
        $this->position = $position;
        $this->label = $label;
        $this->color = $color ?? ProjectSectionTagColor::default();
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

    public function getColor(): ProjectSectionTagColor
    {
        return $this->color;
    }
}
