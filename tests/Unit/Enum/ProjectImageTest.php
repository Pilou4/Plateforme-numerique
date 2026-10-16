<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectImage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Les images d'un projet : nom du champ du formulaire, case « Supprimer », préfixe du fichier et route.
 */
final class ProjectImageTest extends TestCase
{
    /**
     * @return iterable<string, array{ProjectImage, string, string, string, string}>
     */
    public static function images(): iterable
    {
        yield 'logo' => [ProjectImage::Logo, 'logo', 'removeLogo', 'logo', 'app_project_logo'];
        yield 'logo long' => [ProjectImage::LogoWide, 'logoWide', 'removeLogoWide', 'logo-long', 'app_project_logo_wide'];
        yield 'avatar' => [ProjectImage::Avatar, 'avatar', 'removeAvatar', 'avatar', 'app_project_avatar'];
    }

    #[DataProvider('images')]
    public function testFieldsAndRoute(ProjectImage $image, string $field, string $removeField, string $filePrefix, string $route): void
    {
        $this->assertSame($field, $image->value);
        $this->assertSame($removeField, $image->removeField());
        $this->assertSame($filePrefix, $image->filePrefix());
        $this->assertSame($route, $image->routeName());
    }

    public function testImageFoundByTheEndOfItsAddress(): void
    {
        $this->assertSame(ProjectImage::Logo, ProjectImage::tryFromUrlName('logo'));
        $this->assertSame(ProjectImage::LogoWide, ProjectImage::tryFromUrlName('logo-long'));
        $this->assertSame(ProjectImage::Avatar, ProjectImage::tryFromUrlName('avatar'));
        $this->assertNull(ProjectImage::tryFromUrlName('logoWide'));
    }

    public function testLabels(): void
    {
        $this->assertSame(['Logo', 'Logo long', 'Avatar'], array_map(static fn (ProjectImage $image): string => $image->label(), ProjectImage::cases()));
    }
}
