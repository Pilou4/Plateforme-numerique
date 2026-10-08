<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectStatus;
use App\Entity\ProjectStep;
use App\Entity\ProjectSubStep;
use App\Enum\ProjectStepPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectStepTest extends TestCase
{
    public function testNewStepHasDefaultValues(): void
    {
        $step = new ProjectStep();

        // Le statut est donné par ProjectStepManager à la création
        $this->assertNull($step->getStatus());
        $this->assertNull($step->getStatusCode());
        $this->assertFalse($step->isDone());
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

        $step->setStatus(self::status(ProjectStatus::CODE_DONE));

        $this->assertTrue($step->isDone());
        $this->assertSame(ProjectStatus::CODE_DONE, $step->getStatusCode());
        $this->assertNotNull($step->getCompletedAt());
    }

    public function testSettingDoneAgainKeepsTheFirstCompletionDate(): void
    {
        $step = (new ProjectStep())->setStatus(self::status(ProjectStatus::CODE_DONE));
        $completedAt = $step->getCompletedAt();

        $step->setStatus(self::status(ProjectStatus::CODE_DONE));

        $this->assertSame($completedAt, $step->getCompletedAt());
    }

    public function testLeavingDoneClearsTheCompletionDate(): void
    {
        $step = (new ProjectStep())->setStatus(self::status(ProjectStatus::CODE_DONE));

        $step->setStatus(self::status(ProjectStatus::CODE_IN_PROGRESS));

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
     * @param list<string> $subStepCodes codes des statuts des sous-tâches
     */
    #[DataProvider('subStepStatusesProvider')]
    public function testStatusCodeFromSubSteps(array $subStepCodes, string $expected): void
    {
        $step = new ProjectStep();

        foreach ($subStepCodes as $code) {
            $step->addSubStep((new ProjectSubStep())->setStatus(self::status($code)));
        }

        $this->assertSame($expected, $step->getStatusCodeFromSubSteps());
    }

    public static function subStepStatusesProvider(): iterable
    {
        yield 'toutes pas commencées' => [[ProjectStatus::CODE_TODO, ProjectStatus::CODE_TODO], ProjectStatus::CODE_TODO];
        yield 'toutes terminées' => [[ProjectStatus::CODE_DONE, ProjectStatus::CODE_DONE], ProjectStatus::CODE_DONE];
        yield 'une terminée, une pas commencée' => [[ProjectStatus::CODE_DONE, ProjectStatus::CODE_TODO], ProjectStatus::CODE_IN_PROGRESS];
        yield 'une en cours' => [[ProjectStatus::CODE_IN_PROGRESS], ProjectStatus::CODE_IN_PROGRESS];
        yield 'mélange des trois' => [[ProjectStatus::CODE_TODO, ProjectStatus::CODE_IN_PROGRESS, ProjectStatus::CODE_DONE], ProjectStatus::CODE_IN_PROGRESS];
    }

    public function testStatusCodeWithoutSubStepsIsNull(): void
    {
        $this->assertNull((new ProjectStep())->getStatusCodeFromSubSteps());
    }

    /**
     * Statut de la table project_status, créé ici sans base de données.
     */
    private static function status(string $code): ProjectStatus
    {
        return new ProjectStatus($code, $code, $code, array_search($code, ProjectStatus::CODES, true));
    }
}
