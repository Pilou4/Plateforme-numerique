<?php

namespace App\Service;

use App\Dto\ProjectStepPayload;
use App\Entity\ProjectStatus;
use App\Entity\ProjectStep;
use App\Entity\ProjectSubStep;
use App\Repository\ProjectStatusRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Création, modification, suppression et réordonnancement des sous-tâches.
 * Après chaque changement, le statut de la tâche parente est recalculé.
 */
final class ProjectSubStepManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectStatusRepository $projectStatusRepository,
    ) {
    }

    /**
     * Une nouvelle sous-tâche est toujours « pas commencée », avec 0 minute passée.
     *
     * @throws \DomainException si une sous-tâche de cette tâche porte déjà ce titre
     */
    public function create(ProjectStep $step, ProjectStepPayload $payload): ProjectSubStep
    {
        $subStep = new ProjectSubStep();
        $subStep->setStatus($this->projectStatusRepository->getByCode(ProjectStatus::CODE_TODO));
        $subStep->setPosition($this->findNextPosition($step));
        $this->applyTitle($step, $subStep, (string) $payload->title);
        $this->applyDescription($subStep, $payload->description);

        if (null !== $payload->priority) {
            $subStep->setPriority($payload->priority);
        }

        $step->addSubStep($subStep);
        $this->refreshStepStatus($step);

        $this->entityManager->persist($subStep);
        $this->entityManager->flush();

        return $subStep;
    }

    /**
     * @throws \DomainException si une autre sous-tâche de la même tâche porte déjà ce titre
     */
    public function update(ProjectSubStep $subStep, ProjectStepPayload $payload): ProjectSubStep
    {
        $step = $subStep->getStep();

        if (null !== $payload->title) {
            $this->applyTitle($step, $subStep, $payload->title);
        }

        $this->applyDescription($subStep, $payload->description);

        if (null !== $payload->status) {
            $subStep->setStatus($this->projectStatusRepository->getByCode($payload->status));
        }

        if (null !== $payload->priority) {
            $subStep->setPriority($payload->priority);
        }

        if (null !== $payload->timeSpent) {
            $subStep->setTimeSpent($payload->timeSpent);
        }

        $this->refreshStepStatus($step);
        $this->entityManager->flush();

        return $subStep;
    }

    public function delete(ProjectSubStep $subStep): void
    {
        $step = $subStep->getStep();

        // orphanRemoval : retirer la sous-tâche de sa tâche la supprime en base
        $step->removeSubStep($subStep);
        $this->refreshStepStatus($step);
        $this->entityManager->flush();
    }

    /**
     * @param list<int> $ids tous les identifiants des sous-tâches de la tâche, dans le nouvel ordre
     *
     * @throws \InvalidArgumentException si la liste ne correspond pas exactement aux sous-tâches de la tâche
     */
    public function reorder(ProjectStep $step, array $ids): void
    {
        $subSteps = $step->getSubSteps();

        if ($subSteps->count() !== \count($ids)) {
            throw new \InvalidArgumentException('La liste envoyée ne contient pas toutes les sous-tâches.');
        }

        $positions = array_flip(array_values($ids));

        foreach ($subSteps as $subStep) {
            if (!isset($positions[$subStep->getId()])) {
                throw new \InvalidArgumentException('La liste envoyée contient une sous-tâche inconnue.');
            }

            $subStep->setPosition($positions[$subStep->getId()]);
        }

        $this->entityManager->flush();
    }

    /**
     * La tâche prend le statut calculé à partir de ses sous-tâches (voir ProjectStep::getStatusCodeFromSubSteps()).
     * Sans sous-tâche, son statut ne change pas.
     */
    private function refreshStepStatus(ProjectStep $step): void
    {
        $code = $step->getStatusCodeFromSubSteps();

        if (null !== $code && $code !== $step->getStatusCode()) {
            $step->setStatus($this->projectStatusRepository->getByCode($code));
        }
    }

        private function findNextPosition(ProjectStep $step): int
    {
        $maxPosition = -1;

        foreach ($step->getSubSteps() as $subStep) {
            $maxPosition = max($maxPosition, $subStep->getPosition());
        }

        return $maxPosition + 1;
    }

    /**
     * Le titre est unique à l'intérieur d'une même tâche (sans tenir compte des majuscules).
     */
    private function applyTitle(ProjectStep $step, ProjectSubStep $subStep, string $title): void
    {
        $title = trim($title);

        foreach ($step->getSubSteps() as $otherSubStep) {
            if ($otherSubStep !== $subStep && mb_strtolower($otherSubStep->getTitle()) === mb_strtolower($title)) {
                throw new \DomainException(\sprintf('Cette tâche a déjà une sous-tâche « %s ».', $title));
            }
        }

        $subStep->setTitle($title);
    }

    private function applyDescription(ProjectSubStep $subStep, ?string $description): void
    {
        if (null === $description) {
            return;
        }

        // Une description vide efface la description existante
        $description = trim($description);
        $subStep->setDescription('' === $description ? null : $description);
    }
}
