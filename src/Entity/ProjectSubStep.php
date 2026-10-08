<?php

namespace App\Entity;

use App\Enum\ProjectStepPriority;
use App\Repository\ProjectSubStepRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Sous-tâche d'une tâche du projet.
 * Mêmes informations qu'une tâche ; le titre est unique à l'intérieur d'une même tâche.
 */
#[ORM\Entity(repositoryClass: ProjectSubStepRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_project_sub_step_title', columns: ['step_id', 'title'])]
#[ORM\HasLifecycleCallbacks]
class ProjectSubStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([ProjectStep::GROUP_READ])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subSteps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProjectStep $step = null;

    #[ORM\Column(length: 255)]
    #[Groups([ProjectStep::GROUP_READ])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([ProjectStep::GROUP_READ])]
    private ?string $description = null;

    /**
     * Statut (table project_status). Donné par ProjectSubStepManager à la création.
     * Envoyé à l'API sous forme de code : voir getStatusCode().
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectStatus $status = null;

    #[ORM\Column(length: 20, enumType: ProjectStepPriority::class)]
    #[Groups([ProjectStep::GROUP_READ])]
    private ProjectStepPriority $priority = ProjectStepPriority::Normal;

    /**
     * Temps passé, en minutes.
     */
    #[ORM\Column]
    #[Groups([ProjectStep::GROUP_READ])]
    private int $timeSpent = 0;

    #[ORM\Column]
    #[Groups([ProjectStep::GROUP_READ])]
    private int $position = 0;

    #[ORM\Column(nullable: true)]
    #[Groups([ProjectStep::GROUP_READ])]
    private ?\DateTimeImmutable $completedAt = null;

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

    public function getStep(): ?ProjectStep
    {
        return $this->step;
    }

    public function setStep(?ProjectStep $step): static
    {
        $this->step = $step;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?ProjectStatus
    {
        return $this->status;
    }

    /**
     * Code du statut (pas_commencer, en_cours, terminer) : c'est ce que reçoit l'API sous le nom « status ».
     */
    #[Groups([ProjectStep::GROUP_READ])]
    #[SerializedName('status')]
    public function getStatusCode(): ?string
    {
        return $this->status?->getCode();
    }

    public function isDone(): bool
    {
        return true === $this->status?->isDone();
    }

    /**
     * Passer à « terminée » enregistre la date de fin ; quitter « terminée » l'efface.
     */
    public function setStatus(ProjectStatus $status): static
    {
        if ($status->isDone() && !$this->isDone()) {
            $this->completedAt = new \DateTimeImmutable();
        } elseif (!$status->isDone()) {
            $this->completedAt = null;
        }

        $this->status = $status;

        return $this;
    }

    public function getPriority(): ProjectStepPriority
    {
        return $this->priority;
    }

    public function setPriority(ProjectStepPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getTimeSpent(): int
    {
        return $this->timeSpent;
    }

    public function setTimeSpent(int $timeSpent): static
    {
        $this->timeSpent = $timeSpent;

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

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
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
