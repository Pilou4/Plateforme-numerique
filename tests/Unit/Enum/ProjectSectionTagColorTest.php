<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectSectionTagColor;
use PHPUnit\Framework\TestCase;

final class ProjectSectionTagColorTest extends TestCase
{
    public function testValuesAreInFrench(): void
    {
        $this->assertSame(
            ['bleu', 'vert', 'violet', 'orange', 'rouge', 'gris', 'cyan', 'jaune', 'rose'],
            array_map(static fn (ProjectSectionTagColor $color): string => $color->value, ProjectSectionTagColor::cases()),
        );
    }

    public function testDefaultIsBlueAndComesFirst(): void
    {
        $this->assertSame(ProjectSectionTagColor::Blue, ProjectSectionTagColor::default());
        $this->assertSame(ProjectSectionTagColor::default(), ProjectSectionTagColor::cases()[0]);
    }

    public function testEveryColorHasALabelAndAUniqueCssModifier(): void
    {
        $modifiers = [];

        foreach (ProjectSectionTagColor::cases() as $color) {
            $this->assertNotSame('', $color->label());
            $this->assertMatchesRegularExpression('/^[a-z]+$/', $color->cssModifier());
            $modifiers[] = $color->cssModifier();
        }

        $this->assertSame($modifiers, array_unique($modifiers));
    }

    public function testEveryColorHasItsStylesOnThePageAndInTheEditor(): void
    {
        $css = (string) file_get_contents(\dirname(__DIR__, 3).'/assets/styles/app.css');

        foreach (ProjectSectionTagColor::cases() as $color) {
            foreach (['section-block__tag--', 'section-editor__chip--', 'section-editor__swatch--'] as $prefix) {
                $this->assertStringContainsString('.'.$prefix.$color->cssModifier(), $css, $color->label());
            }
        }
    }
}
