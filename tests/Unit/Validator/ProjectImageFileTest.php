<?php

namespace App\Tests\Unit\Validator;

use App\Validator\ProjectAvatar;
use App\Validator\ProjectImageFile;
use App\Validator\ProjectLogo;
use App\Validator\ProjectLogoWide;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Les trois images d'un projet suivent les mêmes règles ; seul le début du message change.
 * (Les règles elles-mêmes sont détaillées dans ProjectLogoTest.)
 */
final class ProjectImageFileTest extends TestCase
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

    /**
     * @return iterable<string, array{ProjectImageFile, string}>
     */
    public static function constraints(): iterable
    {
        yield 'logo' => [new ProjectLogo(), 'Le logo'];
        yield 'logo long' => [new ProjectLogoWide(), 'Le logo long'];
        yield 'avatar' => [new ProjectAvatar(), 'L\'avatar'];
    }

    #[DataProvider('constraints')]
    public function testPngIsAccepted(ProjectImageFile $constraint, string $subject): void
    {
        $this->assertCount(0, $this->validate(base64_decode(self::PNG), $constraint));
    }

    #[DataProvider('constraints')]
    public function testMessageNamesTheImage(ProjectImageFile $constraint, string $subject): void
    {
        $violations = $this->validate("Pas une image\n", $constraint);

        $this->assertCount(1, $violations);
        $this->assertSame($subject.' doit être une image PNG, JPG, WebP ou SVG.', $violations[0]->getMessage());
    }

    #[DataProvider('constraints')]
    public function testSizeMessageNamesTheImage(ProjectImageFile $constraint, string $subject): void
    {
        $violations = $this->validate(str_repeat('a', 2_000_001), $constraint);

        $this->assertCount(1, $violations);
        $this->assertStringStartsWith($subject.' ne doit pas dépasser', $violations[0]->getMessage());
    }

    private function validate(string $content, ProjectImageFile $constraint): ConstraintViolationListInterface
    {
        $path = tempnam(sys_get_temp_dir(), 'image');
        file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return Validation::createValidator()->validate(new File($path), $constraint);
    }
}
