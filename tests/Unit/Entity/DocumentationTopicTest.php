<?php

namespace App\Tests\Unit\Entity;

use App\Entity\DocumentationSection;
use App\Entity\DocumentationTopic;
use PHPUnit\Framework\TestCase;

final class DocumentationTopicTest extends TestCase
{
    public function testAddSectionLinksBothSides(): void
    {
        $topic = new DocumentationTopic();
        $section = new DocumentationSection();

        $topic->addSection($section);
        $topic->addSection($section);

        $this->assertCount(1, $topic->getSections());
        $this->assertSame($topic, $section->getTopic());
    }

    public function testRemoveSectionUnlinksBothSides(): void
    {
        $topic = new DocumentationTopic();
        $section = new DocumentationSection();
        $topic->addSection($section);

        $topic->removeSection($section);

        $this->assertCount(0, $topic->getSections());
        $this->assertNull($section->getTopic());
    }

    public function testRefreshUpdatedAtSetsTheDate(): void
    {
        $topic = new DocumentationTopic();

        $topic->refreshUpdatedAt();

        $this->assertNotNull($topic->getUpdatedAt());
    }

    public function testSectionRefreshUpdatedAtSetsTheDate(): void
    {
        $section = new DocumentationSection();

        $section->refreshUpdatedAt();

        $this->assertNotNull($section->getUpdatedAt());
    }
}
