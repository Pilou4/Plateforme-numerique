<?php

namespace App\Entity;

use App\Repository\ProjectCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Catégorie d'un projet (table project_category) : Application web, Site vitrine, Jeu…
 * Les catégories se gèrent depuis la liste des projets, roue dentée › Catégories (ProjectCategoryManager).
 * La liste de départ est créée par la migration Version20261009120000.
 */
#[ORM\Entity(repositoryClass: ProjectCategoryRepository::class)]
class ProjectCategory implements PositionedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    /**
     * Ordre dans la liste déroulante du formulaire (modifiable depuis la modal « Catégories »).
     */
    #[ORM\Column]
    private int $position;

    public function __construct(string $name, int $position)
    {
        $this->name = $name;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
