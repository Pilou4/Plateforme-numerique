<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectSectionBlockType;
use PHPUnit\Framework\TestCase;

final class ProjectSectionBlockTypeTest extends TestCase
{
    public function testValuesAreInFrench(): void
    {
        $this->assertSame(
            ['paragraphe', 'sous_titre', 'liste', 'tableau', 'tags', 'definitions', 'images', 'documents'],
            array_map(static fn (ProjectSectionBlockType $type): string => $type->value, ProjectSectionBlockType::cases()),
        );
    }

    public function testEveryTypeHasALabelAndAHelp(): void
    {
        foreach (ProjectSectionBlockType::cases() as $type) {
            $this->assertNotSame('', $type->label());
            $this->assertNotSame('', $type->help());
        }
    }

    public function testOnlyImagesAndDocumentsUseFiles(): void
    {
        $withFiles = array_filter(ProjectSectionBlockType::cases(), static fn (ProjectSectionBlockType $type): bool => $type->usesFiles());

        $this->assertSame([ProjectSectionBlockType::Images, ProjectSectionBlockType::Documents], array_values($withFiles));
    }
}
