<?php

namespace App\Entity;

use App\Enum\ProjectFileType;
use App\Repository\ProjectFileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un fichier rattaché à un projet : image (maquette, ressource…) ou document
 * (schéma de base de données, PDF, Word…).
 *
 * Le fichier lui-même est dans le stockage privé :
 * var/storage/projets/{id du projet}/images/ ou /documents/
 */
#[ORM\Entity(repositoryClass: ProjectFileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProjectFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Column(length: 20, enumType: ProjectFileType::class)]
    private ProjectFileType $type = ProjectFileType::Document;

    /**
     * Nom affiché (pré-rempli avec le nom du fichier envoyé, modifiable).
     */
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Nom du fichier sur le disque (aléatoire, jamais celui envoyé par l'utilisateur).
     */
    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    /**
     * Nom d'origine, proposé au téléchargement.
     */
    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $mimeType = null;

    /**
     * Taille en octets.
     */
    #[ORM\Column]
    private int $size = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getType(): ProjectFileType
    {
        return $this->type;
    }

    public function setType(ProjectFileType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isImage(): bool
    {
        return ProjectFileType::Image === $this->type;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Extension d'origine en minuscules (pdf, docx, md…), pour l'icône et l'aperçu.
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo((string) $this->originalName, \PATHINFO_EXTENSION));
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

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
