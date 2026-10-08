<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectStepStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectStepStatusTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function testLabel(ProjectStepStatus $status, string $expected): void
    {
        $this->assertSame($expected, $status->label());
    }

    public static function labelProvider(): iterable
    {
        yield 'pas commencée' => [ProjectStepStatus::Todo, 'Pas commencée'];
        yield 'en cours' => [ProjectStepStatus::InProgress, 'En cours'];
        yield 'faite' => [ProjectStepStatus::Done, 'Faite'];
    }

    #[DataProvider('pluralLabelProvider')]
    public function testPluralLabel(ProjectStepStatus $status, string $expected): void
    {
        $this->assertSame($expected, $status->pluralLabel());
    }

    public static function pluralLabelProvider(): iterable
    {
        yield 'pas commencées' => [ProjectStepStatus::Todo, 'Pas commencées'];
        yield 'en cours' => [ProjectStepStatus::InProgress, 'En cours'];
        yield 'faites' => [ProjectStepStatus::Done, 'Faites'];
    }

    /**
     * Les valeurs sont enregistrées en base et envoyées par la page :
     * les changer casserait les données existantes.
     */
    public function testValuesStored(): void
    {
        $this->assertSame(['todo', 'in_progress', 'done'], array_column(ProjectStepStatus::cases(), 'value'));
    }
}
