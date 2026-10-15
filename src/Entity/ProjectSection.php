<?php

namespace App\Entity;

use App\Repository\ProjectSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une partie de la présentation d'un projet (Vision, Base de données…) :
 * un titre (h2) et des blocs rangés dans l'ordre (paragraphe, liste, tableau, images…).
 *
 * Aucun HTML n'est enregistré : l'app fabrique les balises à l'affichage
 * (templates/app/project/_section_block.html.twig).
 */
#[ORM\Entity(repositoryClass: ProjectSectionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProjectSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    /**
     * Titre de la section, affiché en h2.
     */
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * Ordre de la section dans la présentation du projet : 0, 1, 2…
     */
    #[ORM\Column]
    private int $position = 0;

    /**
     * @var Collection<int, ProjectSectionBlock>
     */
    #[ORM\OneToMany(targetEntity: ProjectSectionBlock::class, mappedBy: 'section', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $blocks;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->blocks = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
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

    /**
     * @return Collection<int, ProjectSectionBlock>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    /**
     * Le bloc est ajouté à la fin de la section.
     */
    public function addBlock(ProjectSectionBlock $block): static
    {
        if (!$this->blocks->contains($block)) {
            $block->setPosition($this->blocks->count());
            $this->blocks->add($block);
            $block->setSection($this);
        }

        return $this;
    }

    /**
     * Retire tous les blocs (ils sont supprimés de la base au prochain flush : orphanRemoval).
     */
    public function clearBlocks(): static
    {
        foreach ($this->blocks->toArray() as $block) {
            $this->blocks->removeElement($block);
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
