<?php

namespace App\Entity;

use App\Enum\ProjectStepPriority;
use App\Enum\ProjectStepStatus;
use App\Repository\ProjectSubStepRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

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

    #[ORM\Column(length: 20, enumType: ProjectStepStatus::class)]
    #[Groups([ProjectStep::GROUP_READ])]
    private ProjectStepStatus $status = ProjectStepStatus::Todo;

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

    public function getStatus(): ProjectStepStatus
    {
        return $this->status;
    }

    /**
     * Passer à "faite" enregistre la date de fin ; quitter "faite" l'efface.
     */
    public function setStatus(ProjectStepStatus $status): static
    {
        if (ProjectStepStatus::Done === $status && ProjectStepStatus::Done !== $this->status) {
            $this->completedAt = new \DateTimeImmutable();
        } elseif (ProjectStepStatus::Done !== $status) {
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
