<?php

namespace App\Service;

use App\Dto\ProjectStepPayload;
use App\Entity\Project;
use App\Entity\ProjectStep;
use App\Repository\ProjectStepRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Création, modification, suppression et réordonnancement des tâches d'un projet.
 */
final class ProjectStepManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectStepRepository $projectStepRepository,
    ) {
    }

    /**
     * Une nouvelle tâche est toujours « pas commencée », avec 0 minute passée :
     * seuls le titre, la description et la priorité sont pris en compte.
     *
     * @throws \DomainException si une tâche du projet porte déjà ce titre
     */
    public function create(Project $project, ProjectStepPayload $payload): ProjectStep
    {
        $step = new ProjectStep();
        $step->setPosition($this->projectStepRepository->findNextPosition($project));
        $project->addStep($step);

        $this->applyTitle($step, (string) $payload->title);
        $this->applyDescription($step, $payload->description);

        if (null !== $payload->priority) {
            $step->setPriority($payload->priority);
        }

        $this->entityManager->persist($step);
        $this->entityManager->flush();

        return $step;
    }

    /**
     * @throws \DomainException si une autre tâche du projet porte déjà ce titre,
     *                          ou si on modifie le temps d'une tâche qui a des sous-tâches
     */
    public function update(ProjectStep $step, ProjectStepPayload $payload): ProjectStep
    {
        if (null !== $payload->title) {
            $this->applyTitle($step, $payload->title);
        }

        $this->applyDescription($step, $payload->description);

        if (null !== $payload->status) {
            $step->setStatus($payload->status);
        }

        if (null !== $payload->priority) {
            $step->setPriority($payload->priority);
        }

        if (null !== $payload->timeSpent) {
            if ($step->hasSubSteps()) {
                throw new \DomainException('Le temps de cette tâche est la somme de ses sous-tâches : modifie le temps des sous-tâches.');
            }

            $step->setTimeSpent($payload->timeSpent);
        }

        $this->entityManager->flush();

        return $step;
    }

    public function delete(ProjectStep $step): void
    {
        $this->entityManager->remove($step);
        $this->entityManager->flush();
    }

    /**
     * @param list<int> $ids tous les identifiants des tâches du projet, dans le nouvel ordre
     *
     * @throws \InvalidArgumentException si la liste ne correspond pas exactement aux tâches du projet
     */
    public function reorder(Project $project, array $ids): void
    {
        $steps = $this->projectStepRepository->findBy(['project' => $project]);

        if (\count($steps) !== \count($ids)) {
            throw new \InvalidArgumentException('La liste envoyée ne contient pas toutes les tâches.');
        }

        $positions = array_flip(array_values($ids));

        foreach ($steps as $step) {
            if (!isset($positions[$step->getId()])) {
                throw new \InvalidArgumentException('La liste envoyée contient une tâche inconnue.');
            }

            $step->setPosition($positions[$step->getId()]);
        }

        $this->entityManager->flush();
    }

    /**
     * Le titre est unique à l'intérieur d'un projet.
     * La comparaison suit la base (MySQL ignore la casse : « Docker » = « docker »).
     */
    private function applyTitle(ProjectStep $step, string $title): void
    {
        $title = trim($title);
        $existingStep = $this->projectStepRepository->findOneBy([
            'project' => $step->getProject(),
            'title' => $title,
        ]);

        if (null !== $existingStep && $existingStep !== $step) {
            throw new \DomainException(\sprintf('Une tâche porte déjà le titre « %s » dans ce projet.', $title));
        }

        $step->setTitle($title);
    }

    private function applyDescription(ProjectStep $step, ?string $description): void
    {
        if (null === $description) {
            return;
        }

        // Une description vide efface la description existante
        $description = trim($description);
        $step->setDescription('' === $description ? null : $description);
    }
}
