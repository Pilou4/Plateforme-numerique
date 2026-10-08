<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectFilePayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ProjectFilePayloadTest extends TestCase
{
    public function testEverythingIsOptional(): void
    {
        // À l'ajout, un titre vide est remplacé par le nom du fichier
        $this->assertCount(0, $this->validate(new ProjectFilePayload()));
    }

    public function testTitleTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectFilePayload(str_repeat('a', 256)));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
    }

    public function testDescriptionTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectFilePayload('Titre', str_repeat('a', 2001)));

        $this->assertCount(1, $violations);
        $this->assertSame('description', $violations[0]->getPropertyPath());
    }

    private function validate(ProjectFilePayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
