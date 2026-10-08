<?php

namespace App\Tests\Unit\Validator;

use App\Validator\ProjectLogo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Le type est lu dans le contenu du fichier : chaque test écrit un vrai fichier temporaire.
 */
final class ProjectLogoTest extends TestCase
{
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    private const string SVG = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"10\" height=\"10\"></svg>\n";

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

    public function testPngIsAccepted(): void
    {
        $this->assertCount(0, $this->validate(base64_decode(self::PNG)));
    }

    public function testSvgIsAccepted(): void
    {
        $this->assertCount(0, $this->validate(self::SVG));
    }

    public function testTextIsRefused(): void
    {
        $violations = $this->validate("Pas une image\n");

        $this->assertCount(1, $violations);
        $this->assertSame('Le logo doit être une image PNG, JPG, WebP ou SVG.', $violations[0]->getMessage());
    }

    public function testFileOverTwoMegabytesIsRefused(): void
    {
        $violations = $this->validate(str_repeat('a', 2_000_001));

        $this->assertCount(1, $violations);
        $this->assertStringStartsWith('Le logo ne doit pas dépasser', $violations[0]->getMessage());
    }

    private function validate(string $content): ConstraintViolationListInterface
    {
        $path = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return Validation::createValidator()->validate(new File($path), new ProjectLogo());
    }
}
