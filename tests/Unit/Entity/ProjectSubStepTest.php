<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectSubStep;
use App\Enum\ProjectStepPriority;
use App\Enum\ProjectStepStatus;
use PHPUnit\Framework\TestCase;

final class ProjectSubStepTest extends TestCase
{
    public function testNewSubStepHasDefaultValues(): void
    {
        $subStep = new ProjectSubStep();

        $this->assertSame(ProjectStepStatus::Todo, $subStep->getStatus());
        $this->assertSame(ProjectStepPriority::Normal, $subStep->getPriority());
        $this->assertSame(0, $subStep->getTimeSpent());
        $this->assertSame(0, $subStep->getPosition());
        $this->assertNull($subStep->getCompletedAt());
    }

    public function testSettingDoneSetsTheCompletionDate(): void
    {
        $subStep = new ProjectSubStep();

        $subStep->setStatus(ProjectStepStatus::Done);

        $this->assertNotNull($subStep->getCompletedAt());
    }

    public function testSettingDoneAgainKeepsTheFirstCompletionDate(): void
    {
        $subStep = (new ProjectSubStep())->setStatus(ProjectStepStatus::Done);
        $completedAt = $subStep->getCompletedAt();

        $subStep->setStatus(ProjectStepStatus::Done);

        $this->assertSame($completedAt, $subStep->getCompletedAt());
    }

    public function testLeavingDoneClearsTheCompletionDate(): void
    {
        $subStep = (new ProjectSubStep())->setStatus(ProjectStepStatus::Done);

        $subStep->setStatus(ProjectStepStatus::Todo);

        $this->assertNull($subStep->getCompletedAt());
    }

    public function testRefreshUpdatedAtSetsTheDate(): void
    {
        $subStep = new ProjectSubStep();

        $subStep->refreshUpdatedAt();

        $this->assertNotNull($subStep->getUpdatedAt());
    }
}
