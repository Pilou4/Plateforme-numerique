<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un projet : sa présentation (découpée en sections) et ses tâches.
 */
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    /**
     * Identifiant utilisé dans les URL : /app/projet/{slug}
     */
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    /**
     * Phrase de présentation courte, affichée dans la liste des projets.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $summary = null;

    /**
     * Nom du fichier du logo dans le stockage privé (var/storage/projets/{id}/).
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ProjectSection>
     */
    #[ORM\OneToMany(targetEntity: ProjectSection::class, mappedBy: 'project', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $sections;

    /**
     * @var Collection<int, ProjectStep>
     */
    #[ORM\OneToMany(targetEntity: ProjectStep::class, mappedBy: 'project', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $steps;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->sections = new ArrayCollection();
        $this->steps = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

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

    /**
     * @return Collection<int, ProjectSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(ProjectSection $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setProject($this);
        }

        return $this;
    }

    public function removeSection(ProjectSection $section): static
    {
        if ($this->sections->removeElement($section) && $section->getProject() === $this) {
            $section->setProject(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectStep>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(ProjectStep $step): static
    {
        if (!$this->steps->contains($step)) {
            $this->steps->add($step);
            $step->setProject($this);
        }

        return $this;
    }

    public function removeStep(ProjectStep $step): static
    {
        if ($this->steps->removeElement($step) && $step->getProject() === $this) {
            $step->setProject(null);
        }

        return $this;
    }
}
