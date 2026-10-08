<?php

namespace App\Tests\Unit\Validator;

use App\Validator\ProjectFileUpload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Le type est lu dans le contenu du fichier (pas dans son extension) :
 * chaque test écrit un vrai fichier temporaire.
 */
final class ProjectFileUploadTest extends TestCase
{
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    #[DataProvider('acceptedContentProvider')]
    public function testAcceptedFile(string $content): void
    {
        $this->assertCount(0, $this->validate($content));
    }

    public static function acceptedContentProvider(): iterable
    {
        yield 'png' => [base64_decode(self::PNG)];
        yield 'svg' => ["<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"10\" height=\"10\"></svg>\n"];
        yield 'pdf' => ["%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n"];
        yield 'texte' => ["Bonjour, ceci est un fichier texte.\n"];
        yield 'markdown' => ["# Titre\n\nUn paragraphe.\n"];
    }

    #[DataProvider('refusedContentProvider')]
    public function testRefusedFile(string $content): void
    {
        $violations = $this->validate($content);

        $this->assertCount(1, $violations);
        $this->assertStringStartsWith('Ce type de fichier n\'est pas accepté', $violations[0]->getMessage());
    }

    public static function refusedContentProvider(): iterable
    {
        // Une page HTML pourrait contenir du JavaScript
        yield 'html' => ["<!DOCTYPE html><html><body><script>alert(1)</script></body></html>"];
        yield 'binaire inconnu' => ["\x00\x01\x02\x03\xFF\xFE\x00\x10binaire"];
    }

    public function testFileOverTenMegabytesIsRefused(): void
    {
        $violations = $this->validate(str_repeat('a', 10_000_001));

        $this->assertCount(1, $violations);
        $this->assertStringStartsWith('Le fichier ne doit pas dépasser', $violations[0]->getMessage());
    }

    private function validate(string $content): ConstraintViolationListInterface
    {
        $path = tempnam(sys_get_temp_dir(), 'fichier');
        file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return Validation::createValidator()->validate(new File($path), new ProjectFileUpload());
    }
}
