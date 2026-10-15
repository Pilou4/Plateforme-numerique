<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectCategoryPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Vérifie les règles écrites avec les attributs #[Assert\…] du DTO.
 */
final class ProjectCategoryPayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $this->assertCount(0, $this->validate(new ProjectCategoryPayload('Application mobile')));
    }

    public function testNameIsRequired(): void
    {
        $violations = $this->validate(new ProjectCategoryPayload(''));

        $this->assertCount(1, $violations);
        $this->assertSame('Le nom de la catégorie est obligatoire.', $violations[0]->getMessage());
    }

    public function testSpacesOnlyAreRefused(): void
    {
        $this->assertCount(1, $this->validate(new ProjectCategoryPayload('   ')));
    }

    public function testNameTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectCategoryPayload(str_repeat('a', 101)));

        $this->assertCount(1, $violations);
        $this->assertSame('name', $violations[0]->getPropertyPath());
    }

    private function validate(ProjectCategoryPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
