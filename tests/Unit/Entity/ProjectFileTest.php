<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectFile;
use App\Enum\ProjectFileType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectFileTest extends TestCase
{
    public function testNewFileIsADocument(): void
    {
        $file = new ProjectFile();

        $this->assertSame(ProjectFileType::Document, $file->getType());
        $this->assertFalse($file->isImage());
    }

    public function testIsImage(): void
    {
        $file = (new ProjectFile())->setType(ProjectFileType::Image);

        $this->assertTrue($file->isImage());
    }

    #[DataProvider('extensionProvider')]
    public function testGetExtension(string $originalName, string $expected): void
    {
        $file = (new ProjectFile())->setOriginalName($originalName);

        $this->assertSame($expected, $file->getExtension());
    }

    public static function extensionProvider(): iterable
    {
        yield 'pdf' => ['cahier-des-charges.pdf', 'pdf'];
        yield 'majuscules' => ['Maquette.PNG', 'png'];
        yield 'plusieurs points' => ['schema.base.de.donnees.md', 'md'];
        yield 'sans extension' => ['LISEZMOI', ''];
    }

    public function testExtensionWithoutOriginalNameIsEmpty(): void
    {
        $this->assertSame('', (new ProjectFile())->getExtension());
    }

    public function testRefreshUpdatedAtSetsTheDate(): void
    {
        $file = new ProjectFile();

        $file->refreshUpdatedAt();

        $this->assertNotNull($file->getUpdatedAt());
    }
}
