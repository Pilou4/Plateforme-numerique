<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Vérifie les règles écrites avec les attributs #[Assert\…] du DTO.
 */
final class ProjectPayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $this->assertCount(0, $this->validate(new ProjectPayload('Plateforme', 'Le cœur de l\'entreprise')));
    }

    public function testSummaryIsOptional(): void
    {
        $this->assertCount(0, $this->validate(new ProjectPayload('Plateforme')));
    }

    public function testNameIsRequired(): void
    {
        $violations = $this->validate(new ProjectPayload(''));

        $this->assertCount(1, $violations);
        $this->assertSame('name', $violations[0]->getPropertyPath());
        $this->assertSame('Le nom du projet est obligatoire.', $violations[0]->getMessage());
    }

    public function testNameTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectPayload(str_repeat('a', 256)));

        $this->assertCount(1, $violations);
        $this->assertSame('name', $violations[0]->getPropertyPath());
    }

    public function testSummaryTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectPayload('Plateforme', str_repeat('a', 256)));

        $this->assertCount(1, $violations);
        $this->assertSame('summary', $violations[0]->getPropertyPath());
    }

    private function validate(ProjectPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
