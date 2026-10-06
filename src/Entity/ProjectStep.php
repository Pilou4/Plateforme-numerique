<?php

namespace App\Entity;

use App\Enum\ProjectStepPriority;
use App\Enum\ProjectStepStatus;
use App\Repository\ProjectStepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: ProjectStepRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_project_step_title', columns: ['project_id', 'title'])]
#[ORM\HasLifecycleCallbacks]
class ProjectStep
{
    public const string GROUP_READ = 'project_step:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_READ])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    /**
     * Unique à l'intérieur d'un même projet.
     */
    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_READ])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::GROUP_READ])]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: ProjectStepStatus::class)]
    #[Groups([self::GROUP_READ])]
    private ProjectStepStatus $status = ProjectStepStatus::Todo;

    #[ORM\Column(length: 20, enumType: ProjectStepPriority::class)]
    #[Groups([self::GROUP_READ])]
    private ProjectStepPriority $priority = ProjectStepPriority::Normal;

    /**
     * Temps passé, en minutes, saisi sur la tâche elle-même.
     * Ignoré dès que la tâche a des sous-tâches : voir getTotalTimeSpent().
     */
    #[ORM\Column]
    #[Groups([self::GROUP_READ])]
    private int $timeSpent = 0;

    #[ORM\Column]
    #[Groups([self::GROUP_READ])]
    private int $position = 0;

    #[ORM\Column(nullable: true)]
    #[Groups([self::GROUP_READ])]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ProjectSubStep>
     */
    #[ORM\OneToMany(targetEntity: ProjectSubStep::class, mappedBy: 'step', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $subSteps;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->subSteps = new ArrayCollection();
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

    /**
     * @return Collection<int, ProjectSubStep>
     */
    public function getSubSteps(): Collection
    {
        return $this->subSteps;
    }

    /**
     * Sous-tâches triées par position, pour l'API.
     *
     * @return list<ProjectSubStep>
     */
    #[Groups([self::GROUP_READ])]
    #[SerializedName('subSteps')]
    public function getOrderedSubSteps(): array
    {
        $subSteps = $this->subSteps->toArray();
        usort($subSteps, static fn (ProjectSubStep $a, ProjectSubStep $b): int => $a->getPosition() <=> $b->getPosition());

        return array_values($subSteps);
    }

    public function hasSubSteps(): bool
    {
        return !$this->subSteps->isEmpty();
    }

    public function addSubStep(ProjectSubStep $subStep): static
    {
        if (!$this->subSteps->contains($subStep)) {
            $this->subSteps->add($subStep);
            $subStep->setStep($this);
        }

        return $this;
    }

    public function removeSubStep(ProjectSubStep $subStep): static
    {
        if ($this->subSteps->removeElement($subStep) && $subStep->getStep() === $this) {
            $subStep->setStep(null);
        }

        return $this;
    }

    /**
     * Temps réellement passé sur la tâche :
     * la somme des sous-tâches si elle en a, sinon son propre temps.
     */
    #[Groups([self::GROUP_READ])]
    public function getTotalTimeSpent(): int
    {
        if (!$this->hasSubSteps()) {
            return $this->timeSpent;
        }

        $total = 0;

        foreach ($this->subSteps as $subStep) {
            $total += $subStep->getTimeSpent();
        }

        return $total;
    }

    /**
     * Statut calculé à partir des sous-tâches :
     * toutes pas commencées → pas commencée, toutes faites → faite, sinon → en cours.
     * Sans sous-tâche, le statut n'est pas modifié.
     */
    public function refreshStatusFromSubSteps(): void
    {
        if (!$this->hasSubSteps()) {
            return;
        }

        $todoCount = 0;
        $doneCount = 0;

        foreach ($this->subSteps as $subStep) {
            match ($subStep->getStatus()) {
                ProjectStepStatus::Todo => ++$todoCount,
                ProjectStepStatus::Done => ++$doneCount,
                ProjectStepStatus::InProgress => null,
            };
        }

        $total = $this->subSteps->count();

        $status = match (true) {
            $todoCount === $total => ProjectStepStatus::Todo,
            $doneCount === $total => ProjectStepStatus::Done,
            default => ProjectStepStatus::InProgress,
        };

        $this->setStatus($status);
    }
}
