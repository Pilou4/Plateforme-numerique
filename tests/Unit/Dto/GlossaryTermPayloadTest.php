<?php

namespace App\Tests\Unit\Dto;

use App\Dto\GlossaryTermPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class GlossaryTermPayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $this->assertCount(0, $this->validate(new GlossaryTermPayload('Docker', '<p>Outil de conteneurs.</p>')));
    }

    public function testTermIsRequired(): void
    {
        $violations = $this->validate(new GlossaryTermPayload('', 'Définition'));

        $this->assertCount(1, $violations);
        $this->assertSame('term', $violations[0]->getPropertyPath());
        $this->assertSame('Le terme est obligatoire.', $violations[0]->getMessage());
    }

    public function testDefinitionIsRequired(): void
    {
        $violations = $this->validate(new GlossaryTermPayload('Docker'));

        $this->assertCount(1, $violations);
        $this->assertSame('definition', $violations[0]->getPropertyPath());
        $this->assertSame('La définition est obligatoire.', $violations[0]->getMessage());
    }

    public function testTermTooLongIsRefused(): void
    {
        $violations = $this->validate(new GlossaryTermPayload(str_repeat('a', 256), 'Définition'));

        $this->assertCount(1, $violations);
        $this->assertSame('term', $violations[0]->getPropertyPath());
    }

    private function validate(GlossaryTermPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
