<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectStateColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Couleurs des statuts : les valeurs sont enregistrées en base (en français),
 * les variantes CSS sont utilisées par les templates (en anglais).
 */
final class ProjectStateColorTest extends TestCase
{
    public function testValuesStoredInTheDatabase(): void
    {
        $this->assertSame(['bleu', 'vert', 'violet', 'orange', 'rouge', 'gris'], ProjectStateColor::values());
    }

    /**
     * @return iterable<string, array{ProjectStateColor, string, string}>
     */
    public static function colors(): iterable
    {
        yield 'bleu' => [ProjectStateColor::Blue, 'Bleu', 'blue'];
        yield 'vert' => [ProjectStateColor::Green, 'Vert', 'green'];
        yield 'violet' => [ProjectStateColor::Violet, 'Violet', 'violet'];
        yield 'orange' => [ProjectStateColor::Orange, 'Orange', 'orange'];
        yield 'rouge' => [ProjectStateColor::Red, 'Rouge', 'red'];
        yield 'gris' => [ProjectStateColor::Grey, 'Gris', 'grey'];
    }

    #[DataProvider('colors')]
    public function testLabelAndCssModifier(ProjectStateColor $color, string $label, string $cssModifier): void
    {
        $this->assertSame($label, $color->label());
        $this->assertSame($cssModifier, $color->cssModifier());
    }
}
