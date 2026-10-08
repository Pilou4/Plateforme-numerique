<?php

namespace App\Entity;

use App\Enum\ProjectStepPriority;
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

    /**
     * Statut (table project_status). Donné par ProjectStepManager à la création.
     * Envoyé à l'API sous forme de code : voir getStatusCode().
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectStatus $status = null;

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

    public function getStatus(): ?ProjectStatus
    {
        return $this->status;
    }

    /**
     * Code du statut (pas_commencer, en_cours, terminer) : c'est ce que reçoit l'API sous le nom « status ».
     */
    #[Groups([self::GROUP_READ])]
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
     * Code du statut que doit avoir la tâche d'après ses sous-tâches :
     * toutes pas commencées → pas_commencer, toutes terminées → terminer, sinon → en_cours.
     * Sans sous-tâche : null (le statut de la tâche se choisit à la main).
     *
     * ProjectSubStepManager applique ce statut après chaque changement d'une sous-tâche.
     */
    public function getStatusCodeFromSubSteps(): ?string
    {
        if (!$this->hasSubSteps()) {
            return null;
        }

        $todoCount = 0;
        $doneCount = 0;

        foreach ($this->subSteps as $subStep) {
            if (true === $subStep->getStatus()?->isTodo()) {
                ++$todoCount;
            } elseif ($subStep->isDone()) {
                ++$doneCount;
            }
        }

        $total = $this->subSteps->count();

        return match (true) {
            $todoCount === $total => ProjectStatus::CODE_TODO,
            $doneCount === $total => ProjectStatus::CODE_DONE,
            default => ProjectStatus::CODE_IN_PROGRESS,
        };
    }
}
