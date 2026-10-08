<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectStatusTest extends TestCase
{
    /**
     * Les codes sont enregistrés dans la table project_status et utilisés par le JavaScript :
     * les changer casserait les données existantes.
     */
    public function testCodesInProgressOrder(): void
    {
        $this->assertSame(['pas_commencer', 'en_cours', 'terminer'], ProjectStatus::CODES);
    }

    public function testConstructorFillsEverything(): void
    {
        $status = new ProjectStatus(ProjectStatus::CODE_DONE, 'Terminée', 'Terminées', 2);

        $this->assertSame('terminer', $status->getCode());
        $this->assertSame('Terminée', $status->getLabel());
        $this->assertSame('Terminées', $status->getPluralLabel());
        $this->assertSame(2, $status->getPosition());
    }

    #[DataProvider('codeProvider')]
    public function testIsTodoAndIsDone(string $code, bool $isTodo, bool $isDone): void
    {
        $status = new ProjectStatus($code, $code, $code, 0);

        $this->assertSame($isTodo, $status->isTodo());
        $this->assertSame($isDone, $status->isDone());
    }

    public static function codeProvider(): iterable
    {
        yield 'pas commencée' => [ProjectStatus::CODE_TODO, true, false];
        yield 'en cours' => [ProjectStatus::CODE_IN_PROGRESS, false, false];
        yield 'terminée' => [ProjectStatus::CODE_DONE, false, true];
    }
}
