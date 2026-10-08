<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectStepPayload;
use App\Entity\ProjectStep;
use App\Entity\ProjectSubStep;
use App\Enum\ProjectStepPriority;
use App\Enum\ProjectStepStatus;
use App\Service\ProjectSubStepManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectSubStepManagerTest extends TestCase
{
    /* ------------------------------------------------------------------
       Création
       ------------------------------------------------------------------ */

    public function testCreateAddsTheSubStepToTheStep(): void
    {
        $step = new ProjectStep();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(ProjectSubStep::class));
        $entityManager->expects($this->once())->method('flush');

        $subStep = (new ProjectSubStepManager($entityManager))->create($step, new ProjectStepPayload(
            title: '  Écrire les tests  ',
            description: '  Un test par comportement  ',
            priority: ProjectStepPriority::High,
        ));

        $this->assertSame($step, $subStep->getStep());
        $this->assertSame('Écrire les tests', $subStep->getTitle());
        $this->assertSame('Un test par comportement', $subStep->getDescription());
        $this->assertSame(ProjectStepPriority::High, $subStep->getPriority());
        $this->assertSame(ProjectStepStatus::Todo, $subStep->getStatus());
    }

    public function testCreatePutsTheSubStepAtTheEnd(): void
    {
        $step = new ProjectStep();
        $step->addSubStep((new ProjectSubStep())->setTitle('A')->setPosition(0));
        $step->addSubStep((new ProjectSubStep())->setTitle('B')->setPosition(4));

        $subStep = $this->createManager()->create($step, new ProjectStepPayload(title: 'C'));

        $this->assertSame(5, $subStep->getPosition());
    }

    public function testCreateWithADuplicateTitleIsRefused(): void
    {
        $step = new ProjectStep();
        $step->addSubStep((new ProjectSubStep())->setTitle('Docker'));

        $this->expectException(\DomainException::class);

        // Majuscules ignorées : « docker » = « Docker »
        $this->createManager()->create($step, new ProjectStepPayload(title: 'docker'));
    }

    public function testCreateRefreshesTheStepStatus(): void
    {
        // Tâche « faite » : une nouvelle sous-tâche pas commencée la fait repasser « en cours »
        $step = new ProjectStep();
        $step->addSubStep((new ProjectSubStep())->setTitle('Faite')->setStatus(ProjectStepStatus::Done));
        $step->refreshStatusFromSubSteps();

        $this->createManager()->create($step, new ProjectStepPayload(title: 'Nouvelle'));

        $this->assertSame(ProjectStepStatus::InProgress, $step->getStatus());
    }

    /* ------------------------------------------------------------------
       Modification
       ------------------------------------------------------------------ */

    public function testUpdateLastSubStepToDoneCompletesTheStep(): void
    {
        $step = new ProjectStep();
        $subStep = (new ProjectSubStep())->setTitle('Seule');
        $step->addSubStep($subStep);

        $this->createManager()->update($subStep, new ProjectStepPayload(status: ProjectStepStatus::Done));

        $this->assertSame(ProjectStepStatus::Done, $step->getStatus());
    }

    public function testUpdateOnlyChangesTheFieldsSent(): void
    {
        $step = new ProjectStep();
        $subStep = (new ProjectSubStep())->setTitle('Titre')->setDescription('Description')->setTimeSpent(10);
        $step->addSubStep($subStep);

        $this->createManager()->update($subStep, new ProjectStepPayload(timeSpent: 25));

        $this->assertSame('Titre', $subStep->getTitle());
        $this->assertSame('Description', $subStep->getDescription());
        $this->assertSame(25, $subStep->getTimeSpent());
    }

    public function testUpdateWithAnEmptyDescriptionClearsIt(): void
    {
        $step = new ProjectStep();
        $subStep = (new ProjectSubStep())->setTitle('Titre')->setDescription('Description');
        $step->addSubStep($subStep);

        $this->createManager()->update($subStep, new ProjectStepPayload(description: '   '));

        $this->assertNull($subStep->getDescription());
    }

    public function testUpdateKeepingItsOwnTitleIsAllowed(): void
    {
        $step = new ProjectStep();
        $subStep = (new ProjectSubStep())->setTitle('Docker');
        $step->addSubStep($subStep);

        $this->createManager()->update($subStep, new ProjectStepPayload(title: 'DOCKER'));

        $this->assertSame('DOCKER', $subStep->getTitle());
    }

    public function testUpdateWithTheTitleOfAnotherSubStepIsRefused(): void
    {
        $step = new ProjectStep();
        $step->addSubStep((new ProjectSubStep())->setTitle('Docker'));
        $subStep = (new ProjectSubStep())->setTitle('MySQL');
        $step->addSubStep($subStep);

        $this->expectException(\DomainException::class);

        $this->createManager()->update($subStep, new ProjectStepPayload(title: 'Docker'));
    }

    /* ------------------------------------------------------------------
       Suppression
       ------------------------------------------------------------------ */

    public function testDeleteRemovesTheSubStepAndRefreshesTheStatus(): void
    {
        $step = new ProjectStep();
        $done = (new ProjectSubStep())->setTitle('Faite')->setStatus(ProjectStepStatus::Done);
        $todo = (new ProjectSubStep())->setTitle('Pas commencée');
        $step->addSubStep($done)->addSubStep($todo);
        $step->refreshStatusFromSubSteps();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        (new ProjectSubStepManager($entityManager))->delete($todo);

        $this->assertCount(1, $step->getSubSteps());
        $this->assertSame(ProjectStepStatus::Done, $step->getStatus());
    }

    /* ------------------------------------------------------------------
       Réordonnancement
       ------------------------------------------------------------------ */

    public function testReorderSetsTheNewPositions(): void
    {
        $step = new ProjectStep();
        $first = $this->createSubStep(10, 0);
        $second = $this->createSubStep(20, 1);
        $step->addSubStep($first)->addSubStep($second);

        $this->createManager()->reorder($step, [20, 10]);

        $this->assertSame(1, $first->getPosition());
        $this->assertSame(0, $second->getPosition());
    }

    public function testReorderWithAMissingSubStepIsRefused(): void
    {
        $step = new ProjectStep();
        $step->addSubStep($this->createSubStep(10, 0))->addSubStep($this->createSubStep(20, 1));

        $this->expectException(\InvalidArgumentException::class);

        $this->createManager()->reorder($step, [10]);
    }

    public function testReorderWithAnUnknownSubStepIsRefused(): void
    {
        $step = new ProjectStep();
        $step->addSubStep($this->createSubStep(10, 0))->addSubStep($this->createSubStep(20, 1));

        $this->expectException(\InvalidArgumentException::class);

        $this->createManager()->reorder($step, [10, 99]);
    }

    private function createManager(): ProjectSubStepManager
    {
        return new ProjectSubStepManager($this->createStub(EntityManagerInterface::class));
    }

    /**
     * Sous-tâche avec un id : normalement donné par la base, ici forcé pour le test.
     */
    private function createSubStep(int $id, int $position): ProjectSubStep
    {
        $subStep = (new ProjectSubStep())->setTitle('Sous-tâche '.$id)->setPosition($position);
        (new \ReflectionProperty(ProjectSubStep::class, 'id'))->setValue($subStep, $id);

        return $subStep;
    }
}
