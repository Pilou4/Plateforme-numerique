<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectStepPayload;
use App\Entity\Project;
use App\Entity\ProjectStatus;
use App\Entity\ProjectStep;
use App\Entity\ProjectSubStep;
use App\Enum\ProjectStepPriority;
use App\Repository\ProjectStatusRepository;
use App\Repository\ProjectStepRepository;
use App\Service\ProjectStepManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le repository est remplacé par un faux (stub) : on lui dit quoi répondre,
 * sans base de données.
 */
final class ProjectStepManagerTest extends TestCase
{
    /**
     * Les 3 statuts de la table project_status, créés ici sans base de données.
     *
     * @var array<string, ProjectStatus>
     */
    private array $statuses = [];

    protected function setUp(): void
    {
        foreach (ProjectStatus::CODES as $position => $code) {
            $this->statuses[$code] = new ProjectStatus($code, $code, $code, $position);
        }
    }

    /* ------------------------------------------------------------------
       Création
       ------------------------------------------------------------------ */

    public function testCreateAddsTheStepToTheProject(): void
    {
        $project = new Project();

        $repository = $this->createStub(ProjectStepRepository::class);
        $repository->method('findNextPosition')->willReturn(3);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(ProjectStep::class));
        $entityManager->expects($this->once())->method('flush');

        $step = (new ProjectStepManager($entityManager, $repository, $this->createStatusRepository()))->create($project, new ProjectStepPayload(
            title: '  Page d\'accueil  ',
            description: '  Première version  ',
        ));

        $this->assertSame($project, $step->getProject());
        $this->assertSame('Page d\'accueil', $step->getTitle());
        $this->assertSame('Première version', $step->getDescription());
        $this->assertSame(3, $step->getPosition());
        $this->assertSame(ProjectStatus::CODE_TODO, $step->getStatusCode());
        $this->assertSame(ProjectStepPriority::Normal, $step->getPriority());
    }

    public function testCreateWithAPriority(): void
    {
        $step = $this->createManager()->create(new Project(), new ProjectStepPayload(title: 'Urgent', priority: ProjectStepPriority::High));

        $this->assertSame(ProjectStepPriority::High, $step->getPriority());
    }

    public function testCreateIgnoresStatusAndTime(): void
    {
        $step = $this->createManager()->create(new Project(), new ProjectStepPayload(
            title: 'Tâche',
            status: ProjectStatus::CODE_DONE,
            timeSpent: 120,
        ));

        $this->assertSame(ProjectStatus::CODE_TODO, $step->getStatusCode());
        $this->assertSame(0, $step->getTimeSpent());
    }

    public function testCreateWithADuplicateTitleIsRefused(): void
    {
        // Le repository trouve déjà une tâche avec ce titre dans le projet
        $manager = $this->createManager(existingStep: (new ProjectStep())->setTitle('Docker'));

        $this->expectException(\DomainException::class);

        $manager->create(new Project(), new ProjectStepPayload(title: 'Docker'));
    }

    /* ------------------------------------------------------------------
       Modification
       ------------------------------------------------------------------ */

    public function testUpdateOnlyChangesTheFieldsSent(): void
    {
        $step = (new ProjectStep())->setTitle('Titre')->setDescription('Description');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        (new ProjectStepManager($entityManager, $this->createStub(ProjectStepRepository::class), $this->createStatusRepository()))
            ->update($step, new ProjectStepPayload(priority: ProjectStepPriority::Low, timeSpent: 30));

        $this->assertSame('Titre', $step->getTitle());
        $this->assertSame('Description', $step->getDescription());
        $this->assertSame(ProjectStepPriority::Low, $step->getPriority());
        $this->assertSame(30, $step->getTimeSpent());
    }

    public function testUpdateStatusToDoneSetsTheCompletionDate(): void
    {
        $step = (new ProjectStep())->setTitle('Titre');

        $this->createManager()->update($step, new ProjectStepPayload(status: ProjectStatus::CODE_DONE));

        $this->assertSame($this->statuses[ProjectStatus::CODE_DONE], $step->getStatus());
        $this->assertNotNull($step->getCompletedAt());
    }

    public function testUpdateWithAnEmptyDescriptionClearsIt(): void
    {
        $step = (new ProjectStep())->setTitle('Titre')->setDescription('Description');

        $this->createManager()->update($step, new ProjectStepPayload(description: ''));

        $this->assertNull($step->getDescription());
    }

    public function testUpdateKeepingItsOwnTitleIsAllowed(): void
    {
        $step = (new ProjectStep())->setTitle('Docker');

        // Le repository trouve… la tâche elle-même : ce n'est pas un doublon
        $this->createManager(existingStep: $step)->update($step, new ProjectStepPayload(title: 'Docker'));

        $this->assertSame('Docker', $step->getTitle());
    }

    public function testUpdateWithTheTitleOfAnotherStepIsRefused(): void
    {
        $step = (new ProjectStep())->setTitle('MySQL');
        $manager = $this->createManager(existingStep: (new ProjectStep())->setTitle('Docker'));

        $this->expectException(\DomainException::class);

        $manager->update($step, new ProjectStepPayload(title: 'Docker'));
    }

    public function testUpdateTimeOfAStepWithSubStepsIsRefused(): void
    {
        // Son temps est la somme de ses sous-tâches
        $step = (new ProjectStep())->setTitle('Titre');
        $step->addSubStep((new ProjectSubStep())->setTitle('Sous-tâche'));

        $this->expectException(\DomainException::class);

        $this->createManager()->update($step, new ProjectStepPayload(timeSpent: 60));
    }

    /* ------------------------------------------------------------------
       Suppression
       ------------------------------------------------------------------ */

    public function testDeleteRemovesTheStep(): void
    {
        $step = new ProjectStep();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($step);
        $entityManager->expects($this->once())->method('flush');

        (new ProjectStepManager($entityManager, $this->createStub(ProjectStepRepository::class), $this->createStatusRepository()))->delete($step);
    }

    /* ------------------------------------------------------------------
       Réordonnancement
       ------------------------------------------------------------------ */

    public function testReorderSetsTheNewPositions(): void
    {
        $first = $this->createStep(10, 0);
        $second = $this->createStep(20, 1);
        $third = $this->createStep(30, 2);

        $this->createManager(projectSteps: [$first, $second, $third])->reorder(new Project(), [30, 10, 20]);

        $this->assertSame([1, 2, 0], [$first->getPosition(), $second->getPosition(), $third->getPosition()]);
    }

    public function testReorderWithAMissingStepIsRefused(): void
    {
        $manager = $this->createManager(projectSteps: [$this->createStep(10, 0), $this->createStep(20, 1)]);

        $this->expectException(\InvalidArgumentException::class);

        $manager->reorder(new Project(), [10]);
    }

    public function testReorderWithAnUnknownStepIsRefused(): void
    {
        $manager = $this->createManager(projectSteps: [$this->createStep(10, 0), $this->createStep(20, 1)]);

        $this->expectException(\InvalidArgumentException::class);

        $manager->reorder(new Project(), [10, 99]);
    }

    /**
     * @param ProjectStep|null  $existingStep ce que findOneBy() renvoie (tâche déjà existante avec ce titre)
     * @param list<ProjectStep> $projectSteps ce que findBy() renvoie (toutes les tâches du projet)
     */
    private function createManager(?ProjectStep $existingStep = null, array $projectSteps = []): ProjectStepManager
    {
        $repository = $this->createStub(ProjectStepRepository::class);
        $repository->method('findOneBy')->willReturn($existingStep);
        $repository->method('findBy')->willReturn($projectSteps);
        $repository->method('findNextPosition')->willReturn(0);

        return new ProjectStepManager($this->createStub(EntityManagerInterface::class), $repository, $this->createStatusRepository());
    }

    /**
     * Tâche avec un id : normalement donné par la base, ici forcé pour le test.
     */
    private function createStep(int $id, int $position): ProjectStep
    {
        $step = (new ProjectStep())->setTitle('Tâche '.$id)->setPosition($position);
        (new \ReflectionProperty(ProjectStep::class, 'id'))->setValue($step, $id);

        return $step;
    }

    /**
     * Faux repository des statuts : getByCode() renvoie le statut correspondant de $this->statuses.
     */
    private function createStatusRepository(): ProjectStatusRepository
    {
        $repository = $this->createStub(ProjectStatusRepository::class);
        $repository->method('getByCode')->willReturnCallback(fn (string $code): ProjectStatus => $this->statuses[$code]);

        return $repository;
    }
}
