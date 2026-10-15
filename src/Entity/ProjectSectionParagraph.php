<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne d'un bloc paragraphe (table project_section_paragraph), affichée dans un <p>.
 * Le texte peut contenir la mise en forme simple : **gras**, *italique*, `code`, [lien](https://…).
 * Créé par ProjectSectionBlock (add…) : il ne change plus ensuite, la section est réenregistrée en entier.
 */
#[ORM\Entity]
class ProjectSectionParagraph
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paragraphs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectSectionBlock $block;

    #[ORM\Column]
    private int $position;

    #[ORM\Column(type: Types::TEXT)]
    private string $text;

    public function __construct(ProjectSectionBlock $block, int $position, string $text)
    {
        $this->block = $block;
        $this->position = $position;
        $this->text = $text;
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

    public function getText(): string
    {
        return $this->text;
    }
}
