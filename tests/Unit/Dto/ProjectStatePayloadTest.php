<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectStatePayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Vérifie les règles écrites avec les attributs #[Assert\…] du DTO.
 */
final class ProjectStatePayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $this->assertCount(0, $this->validate(new ProjectStatePayload('En pause', 'En pause', 'orange')));
    }

    public function testPluralLabelIsOptional(): void
    {
        $this->assertCount(0, $this->validate(new ProjectStatePayload('En pause', null, 'gris')));
    }

    public function testLabelIsRequired(): void
    {
        $violations = $this->validate(new ProjectStatePayload('  ', null, 'gris'));

        $this->assertCount(1, $violations);
        $this->assertSame('label', $violations[0]->getPropertyPath());
        $this->assertSame('Le nom du statut est obligatoire.', $violations[0]->getMessage());
    }

    public function testLabelTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectStatePayload(str_repeat('a', 51), null, 'gris'));

        $this->assertCount(1, $violations);
        $this->assertSame('label', $violations[0]->getPropertyPath());
    }

    public function testColorIsRequired(): void
    {
        $violations = $this->validate(new ProjectStatePayload('En pause'));

        $this->assertCount(1, $violations);
        $this->assertSame('Choisis une couleur.', $violations[0]->getMessage());
    }

    public function testUnknownColorIsRefused(): void
    {
        $violations = $this->validate(new ProjectStatePayload('En pause', null, 'rose'));

        $this->assertCount(1, $violations);
        $this->assertSame('Cette couleur n\'existe pas.', $violations[0]->getMessage());
    }

    private function validate(ProjectStatePayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
