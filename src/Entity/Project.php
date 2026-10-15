<?php

namespace App\Entity;

use App\Enum\ProjectImage;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un projet : sa présentation (découpée en sections), ses tâches et ses fichiers.
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

    /**
     * Logo long, horizontal (même stockage que le logo). Voir App\Enum\ProjectImage.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoWide = null;

    /**
     * Avatar, petite image ronde (même stockage que le logo). Voir App\Enum\ProjectImage.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    /**
     * État d'avancement (table project_state). Donné par ProjectManager à la création.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectState $state = null;

    /**
     * Catégorie (table project_category), facultative.
     * Si la catégorie est supprimée de la table, le projet n'a simplement plus de catégorie.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProjectCategory $category = null;

    /**
     * Projet marqué d'une étoile dans la liste (filtre « Favoris »).
     */
    #[ORM\Column]
    private bool $favorite = false;

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

    /**
     * Images et documents du projet.
     * Supprimer le projet supprime ces lignes, mais pas les fichiers sur le disque :
     * ProjectFileManager s'en charge quand on supprime un fichier.
     *
     * @var Collection<int, ProjectFile>
     */
    #[ORM\OneToMany(targetEntity: ProjectFile::class, mappedBy: 'project', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $files;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->sections = new ArrayCollection();
        $this->steps = new ArrayCollection();
        $this->files = new ArrayCollection();
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

    public function getLogoWide(): ?string
    {
        return $this->logoWide;
    }

    public function setLogoWide(?string $logoWide): static
    {
        $this->logoWide = $logoWide;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Nom du fichier d'une des images du projet (logo, logo long ou avatar), null si elle n'existe pas.
     */
    public function getImage(ProjectImage $image): ?string
    {
        return match ($image) {
            ProjectImage::Logo => $this->logo,
            ProjectImage::LogoWide => $this->logoWide,
            ProjectImage::Avatar => $this->avatar,
        };
    }

    public function setImage(ProjectImage $image, ?string $fileName): static
    {
        match ($image) {
            ProjectImage::Logo => $this->logo = $fileName,
            ProjectImage::LogoWide => $this->logoWide = $fileName,
            ProjectImage::Avatar => $this->avatar = $fileName,
        };

        return $this;
    }

    public function getState(): ?ProjectState
    {
        return $this->state;
    }

    public function setState(ProjectState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCategory(): ?ProjectCategory
    {
        return $this->category;
    }

    public function setCategory(?ProjectCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->favorite;
    }

    public function setFavorite(bool $favorite): static
    {
        $this->favorite = $favorite;

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

    /**
     * @return Collection<int, ProjectFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(ProjectFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setProject($this);
        }

        return $this;
    }

    public function removeFile(ProjectFile $file): static
    {
        if ($this->files->removeElement($file) && $file->getProject() === $this) {
            $file->setProject(null);
        }

        return $this;
    }
}
