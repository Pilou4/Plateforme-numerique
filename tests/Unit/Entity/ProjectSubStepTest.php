<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectStatus;
use App\Entity\ProjectSubStep;
use App\Enum\ProjectStepPriority;
use PHPUnit\Framework\TestCase;

final class ProjectSubStepTest extends TestCase
{
    public function testNewSubStepHasDefaultValues(): void
    {
        $subStep = new ProjectSubStep();

        // Le statut est donné par ProjectSubStepManager à la création
        $this->assertNull($subStep->getStatus());
        $this->assertSame(ProjectStepPriority::Normal, $subStep->getPriority());
        $this->assertSame(0, $subStep->getTimeSpent());
        $this->assertSame(0, $subStep->getPosition());
        $this->assertNull($subStep->getCompletedAt());
    }

    public function testSettingDoneSetsTheCompletionDate(): void
    {
        $subStep = new ProjectSubStep();

        $subStep->setStatus(self::status(ProjectStatus::CODE_DONE));

        $this->assertTrue($subStep->isDone());
        $this->assertSame(ProjectStatus::CODE_DONE, $subStep->getStatusCode());
        $this->assertNotNull($subStep->getCompletedAt());
    }

    public function testSettingDoneAgainKeepsTheFirstCompletionDate(): void
    {
        $subStep = (new ProjectSubStep())->setStatus(self::status(ProjectStatus::CODE_DONE));
        $completedAt = $subStep->getCompletedAt();

        $subStep->setStatus(self::status(ProjectStatus::CODE_DONE));

        $this->assertSame($completedAt, $subStep->getCompletedAt());
    }

    public function testLeavingDoneClearsTheCompletionDate(): void
    {
        $subStep = (new ProjectSubStep())->setStatus(self::status(ProjectStatus::CODE_DONE));

        $subStep->setStatus(self::status(ProjectStatus::CODE_TODO));

        $this->assertNull($subStep->getCompletedAt());
    }

    public function testRefreshUpdatedAtSetsTheDate(): void
    {
        $subStep = new ProjectSubStep();

        $subStep->refreshUpdatedAt();

        $this->assertNotNull($subStep->getUpdatedAt());
    }

    /**
     * Statut de la table project_status, créé ici sans base de données.
     */
    private static function status(string $code): ProjectStatus
    {
        return new ProjectStatus($code, $code, $code, array_search($code, ProjectStatus::CODES, true));
    }
}
