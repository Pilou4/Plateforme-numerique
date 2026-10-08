<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectFileType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectFileTypeTest extends TestCase
{
    #[DataProvider('mimeTypeProvider')]
    public function testFromMimeType(string $mimeType, ProjectFileType $expected): void
    {
        $this->assertSame($expected, ProjectFileType::fromMimeType($mimeType));
    }

    public static function mimeTypeProvider(): iterable
    {
        yield 'png' => ['image/png', ProjectFileType::Image];
        yield 'jpeg' => ['image/jpeg', ProjectFileType::Image];
        yield 'webp' => ['image/webp', ProjectFileType::Image];
        yield 'svg' => ['image/svg+xml', ProjectFileType::Image];
        yield 'pdf' => ['application/pdf', ProjectFileType::Document];
        yield 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', ProjectFileType::Document];
        yield 'texte' => ['text/plain', ProjectFileType::Document];
        yield 'inconnu' => ['application/octet-stream', ProjectFileType::Document];
    }

    #[DataProvider('folderProvider')]
    public function testFolder(ProjectFileType $type, string $expected): void
    {
        $this->assertSame($expected, $type->folder());
    }

    public static function folderProvider(): iterable
    {
        yield 'image' => [ProjectFileType::Image, 'images'];
        yield 'document' => [ProjectFileType::Document, 'documents'];
    }

    #[DataProvider('labelProvider')]
    public function testLabel(ProjectFileType $type, string $expected): void
    {
        $this->assertSame($expected, $type->label());
    }

    public static function labelProvider(): iterable
    {
        yield 'image' => [ProjectFileType::Image, 'Image'];
        yield 'document' => [ProjectFileType::Document, 'Document'];
    }
}
