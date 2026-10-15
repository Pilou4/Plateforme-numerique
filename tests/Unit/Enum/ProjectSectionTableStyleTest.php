<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectSectionTableStyle;
use PHPUnit\Framework\TestCase;

final class ProjectSectionTableStyleTest extends TestCase
{
    public function testValuesAreInFrenchAndFitTheColumn(): void
    {
        $this->assertSame(
            ['liste_finder', 'entete_marine', 'gris_tableur', 'carte_douce', 'entete_bleu', 'grille', 'lignes_cartes', 'sombre', 'trait_degrade', 'entete_pilule', 'fiche'],
            array_map(static fn (ProjectSectionTableStyle $style): string => $style->value, ProjectSectionTableStyle::cases()),
        );

        foreach (ProjectSectionTableStyle::cases() as $style) {
            // Colonne project_section_block.table_style : VARCHAR(20)
            $this->assertLessThanOrEqual(20, \strlen($style->value));
        }
    }

    public function testDefaultIsTheFinderListAndComesFirst(): void
    {
        $this->assertSame(ProjectSectionTableStyle::Finder, ProjectSectionTableStyle::default());
        $this->assertSame(ProjectSectionTableStyle::default(), ProjectSectionTableStyle::cases()[0]);
    }

    public function testEveryStyleHasALabelAndAUniqueCssModifier(): void
    {
        $modifiers = [];

        foreach (ProjectSectionTableStyle::cases() as $style) {
            $this->assertNotSame('', $style->label());
            $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+)*$/', $style->cssModifier());
            $modifiers[] = $style->cssModifier();
        }

        $this->assertSame($modifiers, array_unique($modifiers));
    }

    public function testEveryCssModifierHasItsStyles(): void
    {
        $css = (string) file_get_contents(\dirname(__DIR__, 3).'/assets/styles/app.css');

        foreach (ProjectSectionTableStyle::cases() as $style) {
            $this->assertStringContainsString('.section-block__table-wrap--'.$style->cssModifier().' ', $css, $style->label());
        }
    }
}
