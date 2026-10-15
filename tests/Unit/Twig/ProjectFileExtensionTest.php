<?php

namespace App\Tests\Unit\Twig;

use App\Entity\ProjectFile;
use App\Enum\ProjectFileType;
use App\Service\ProjectFileManager;
use App\Service\ProjectFilePreview;
use App\Service\ProjectStorage;
use App\Twig\ProjectFileExtension;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ProjectFileExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function kindProvider(): iterable
    {
        yield 'pdf' => ['dictionnaire.pdf', 'pdf'];
        yield 'word' => ['cahier.docx', 'word'];
        yield 'word en majuscules' => ['ANCIEN.DOC', 'word'];
        yield 'excel' => ['budget.xlsx', 'excel'];
        yield 'csv' => ['export.csv', 'excel'];
        yield 'powerpoint' => ['client.pptx', 'powerpoint'];
        yield 'markdown' => ['notes.md', 'text'];
        yield 'autre' => ['archive.zip', 'other'];
    }

    #[DataProvider('kindProvider')]
    public function testKindFollowsTheExtension(string $name, string $kind): void
    {
        $this->assertSame($kind, ProjectFileExtension::kind((new ProjectFile())->setOriginalName($name)));
    }

    public function testFunctionsGiveThePreviewAndTheKind(): void
    {
        $storage = new ProjectStorage(sys_get_temp_dir().'/plateforme-tests-inutilise', new Filesystem());
        $extension = new ProjectFileExtension(new ProjectFilePreview(new ProjectFileManager($this->createStub(EntityManagerInterface::class), $storage)));
        $functions = [];

        foreach ($extension->getFunctions() as $function) {
            $functions[$function->getName()] = $function->getCallable();
        }

        $pdf = (new ProjectFile())->setOriginalName('schema.pdf')->setType(ProjectFileType::Document)->setMimeType('application/pdf');
        $word = (new ProjectFile())->setOriginalName('cahier.docx')->setType(ProjectFileType::Document)->setMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->assertSame('pdf', $functions['file_preview']($pdf));
        $this->assertNull($functions['file_preview']($word));
        $this->assertSame('word', $functions['file_kind']($word));
    }
}
