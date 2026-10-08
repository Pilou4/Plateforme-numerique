<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectStep;
use App\Entity\ProjectSubStep;
use App\Enum\ProjectStepPriority;
use App\Enum\ProjectStepStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectStepTest extends TestCase
{
    public function testNewStepHasDefaultValues(): void
    {
        $step = new ProjectStep();

        $this->assertSame(ProjectStepStatus::Todo, $step->getStatus());
        $this->assertSame(ProjectStepPriority::Normal, $step->getPriority());
        $this->assertSame(0, $step->getTimeSpent());
        $this->assertNull($step->getCompletedAt());
        $this->assertNull($step->getUpdatedAt());
        $this->assertFalse($step->hasSubSteps());
    }

    public function testRefreshUpdatedAtSetsTheDate(): void
    {
        $step = new ProjectStep();

        $step->refreshUpdatedAt();

        $this->assertNotNull($step->getUpdatedAt());
    }

    /* ------------------------------------------------------------------
       Statut et date de fin
       ------------------------------------------------------------------ */

    public function testSettingDoneSetsTheCompletionDate(): void
    {
        $step = new ProjectStep();

        $step->setStatus(ProjectStepStatus::Done);

        $this->assertNotNull($step->getCompletedAt());
    }

    public function testSettingDoneAgainKeepsTheFirstCompletionDate(): void
    {
        $step = (new ProjectStep())->setStatus(ProjectStepStatus::Done);
        $completedAt = $step->getCompletedAt();

        $step->setStatus(ProjectStepStatus::Done);

        $this->assertSame($completedAt, $step->getCompletedAt());
    }

    public function testLeavingDoneClearsTheCompletionDate(): void
    {
        $step = (new ProjectStep())->setStatus(ProjectStepStatus::Done);

        $step->setStatus(ProjectStepStatus::InProgress);

        $this->assertNull($step->getCompletedAt());
    }

    /* ------------------------------------------------------------------
       Sous-tâches
       ------------------------------------------------------------------ */

    public function testAddSubStepLinksBothSides(): void
    {
        $step = new ProjectStep();
        $subStep = new ProjectSubStep();

        $step->addSubStep($subStep);

        $this->assertTrue($step->hasSubSteps());
        $this->assertSame($step, $subStep->getStep());
    }

    public function testAddSubStepTwiceAddsItOnce(): void
    {
        $step = new ProjectStep();
        $subStep = new ProjectSubStep();

        $step->addSubStep($subStep);
        $step->addSubStep($subStep);

        $this->assertCount(1, $step->getSubSteps());
    }

    public function testRemoveSubStepUnlinksBothSides(): void
    {
        $step = new ProjectStep();
        $subStep = new ProjectSubStep();
        $step->addSubStep($subStep);

        $step->removeSubStep($subStep);

        $this->assertFalse($step->hasSubSteps());
        $this->assertNull($subStep->getStep());
    }

    public function testOrderedSubStepsAreSortedByPosition(): void
    {
        $step = new ProjectStep();
        $third = (new ProjectSubStep())->setTitle('Troisième')->setPosition(2);
        $first = (new ProjectSubStep())->setTitle('Première')->setPosition(0);
        $second = (new ProjectSubStep())->setTitle('Deuxième')->setPosition(1);
        $step->addSubStep($third)->addSubStep($first)->addSubStep($second);

        $this->assertSame([$first, $second, $third], $step->getOrderedSubSteps());
    }

    /* ------------------------------------------------------------------
       Temps passé
       ------------------------------------------------------------------ */

    public function testTotalTimeWithoutSubStepsIsTheStepTime(): void
    {
        $step = (new ProjectStep())->setTimeSpent(45);

        $this->assertSame(45, $step->getTotalTimeSpent());
    }

    public function testTotalTimeWithSubStepsIsTheSumOfTheSubSteps(): void
    {
        // Le temps saisi sur la tâche elle-même est ignoré dès qu'elle a des sous-tâches
        $step = (new ProjectStep())->setTimeSpent(999);
        $step->addSubStep((new ProjectSubStep())->setTimeSpent(30));
        $step->addSubStep((new ProjectSubStep())->setTimeSpent(15));

        $this->assertSame(45, $step->getTotalTimeSpent());
    }

    /* ------------------------------------------------------------------
       Statut calculé à partir des sous-tâches
       ------------------------------------------------------------------ */

    /**
     * @param list<ProjectStepStatus> $subStepStatuses
     */
    #[DataProvider('subStepStatusesProvider')]
    public function testRefreshStatusFromSubSteps(array $subStepStatuses, ProjectStepStatus $expected): void
    {
        $step = new ProjectStep();

        foreach ($subStepStatuses as $status) {
            $step->addSubStep((new ProjectSubStep())->setStatus($status));
        }

        $step->refreshStatusFromSubSteps();

        $this->assertSame($expected, $step->getStatus());
    }

    public static function subStepStatusesProvider(): iterable
    {
        yield 'toutes pas commencées' => [[ProjectStepStatus::Todo, ProjectStepStatus::Todo], ProjectStepStatus::Todo];
        yield 'toutes faites' => [[ProjectStepStatus::Done, ProjectStepStatus::Done], ProjectStepStatus::Done];
        yield 'une faite, une pas commencée' => [[ProjectStepStatus::Done, ProjectStepStatus::Todo], ProjectStepStatus::InProgress];
        yield 'une en cours' => [[ProjectStepStatus::InProgress], ProjectStepStatus::InProgress];
        yield 'mélange des trois' => [[ProjectStepStatus::Todo, ProjectStepStatus::InProgress, ProjectStepStatus::Done], ProjectStepStatus::InProgress];
    }

    public function testRefreshStatusWithoutSubStepsKeepsTheStatus(): void
    {
        $step = (new ProjectStep())->setStatus(ProjectStepStatus::InProgress);

        $step->refreshStatusFromSubSteps();

        $this->assertSame(ProjectStepStatus::InProgress, $step->getStatus());
    }

    public function testAllSubStepsDoneSetsTheCompletionDate(): void
    {
        $step = new ProjectStep();
        $step->addSubStep((new ProjectSubStep())->setStatus(ProjectStepStatus::Done));

        $step->refreshStatusFromSubSteps();

        $this->assertNotNull($step->getCompletedAt());
    }
}
