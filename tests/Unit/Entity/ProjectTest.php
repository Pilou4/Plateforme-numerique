<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Entity\ProjectSection;
use App\Entity\ProjectStep;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function testNewProjectIsEmpty(): void
    {
        $project = new Project();

        $this->assertCount(0, $project->getSections());
        $this->assertCount(0, $project->getSteps());
        $this->assertCount(0, $project->getFiles());
        $this->assertNull($project->getUpdatedAt());
    }

    public function testRefreshUpdatedAtSetsTheDate(): void
    {
        $project = new Project();

        $project->refreshUpdatedAt();

        $this->assertNotNull($project->getUpdatedAt());
    }

    /* ------------------------------------------------------------------
       Sections
       ------------------------------------------------------------------ */

    public function testAddSectionLinksBothSides(): void
    {
        $project = new Project();
        $section = new ProjectSection();

        $project->addSection($section);
        $project->addSection($section);

        $this->assertCount(1, $project->getSections());
        $this->assertSame($project, $section->getProject());
    }

    public function testRemoveSectionUnlinksBothSides(): void
    {
        $project = new Project();
        $section = new ProjectSection();
        $project->addSection($section);

        $project->removeSection($section);

        $this->assertCount(0, $project->getSections());
        $this->assertNull($section->getProject());
    }

    /* ------------------------------------------------------------------
       Tâches
       ------------------------------------------------------------------ */

    public function testAddStepLinksBothSides(): void
    {
        $project = new Project();
        $step = new ProjectStep();

        $project->addStep($step);
        $project->addStep($step);

        $this->assertCount(1, $project->getSteps());
        $this->assertSame($project, $step->getProject());
    }

    public function testRemoveStepUnlinksBothSides(): void
    {
        $project = new Project();
        $step = new ProjectStep();
        $project->addStep($step);

        $project->removeStep($step);

        $this->assertCount(0, $project->getSteps());
        $this->assertNull($step->getProject());
    }

    /* ------------------------------------------------------------------
       Fichiers
       ------------------------------------------------------------------ */

    public function testAddFileLinksBothSides(): void
    {
        $project = new Project();
        $file = new ProjectFile();

        $project->addFile($file);
        $project->addFile($file);

        $this->assertCount(1, $project->getFiles());
        $this->assertSame($project, $file->getProject());
    }

    public function testRemoveFileUnlinksBothSides(): void
    {
        $project = new Project();
        $file = new ProjectFile();
        $project->addFile($file);

        $project->removeFile($file);

        $this->assertCount(0, $project->getFiles());
        $this->assertNull($file->getProject());
    }
}
