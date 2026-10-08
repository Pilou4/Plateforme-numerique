<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectStepPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectStepPriorityTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function testLabel(ProjectStepPriority $priority, string $expected): void
    {
        $this->assertSame($expected, $priority->label());
    }

    public static function labelProvider(): iterable
    {
        yield 'basse' => [ProjectStepPriority::Low, 'Basse'];
        yield 'normale' => [ProjectStepPriority::Normal, 'Normale'];
        yield 'haute' => [ProjectStepPriority::High, 'Haute'];
    }

    /**
     * Les valeurs sont enregistrées en base : les changer casserait les données existantes.
     */
    public function testValuesStored(): void
    {
        $this->assertSame(['low', 'normal', 'high'], array_column(ProjectStepPriority::cases(), 'value'));
    }
}
