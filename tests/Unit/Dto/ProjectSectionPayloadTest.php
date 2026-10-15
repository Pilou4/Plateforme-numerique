<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectSectionPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ProjectSectionPayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $payload = new ProjectSectionPayload('Base de données', [['type' => 'paragraphe', 'text' => 'Texte']]);

        $this->assertCount(0, $this->validate($payload));
    }

    public function testSectionWithoutBlocksIsAccepted(): void
    {
        $this->assertCount(0, $this->validate(new ProjectSectionPayload('Titre')));
    }

    public function testTitleIsRequired(): void
    {
        $violations = $this->validate(new ProjectSectionPayload('   '));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
    }

    public function testTitleTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectSectionPayload(str_repeat('a', 256)));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
    }

    public function testTooManyBlocksAreRefused(): void
    {
        $blocks = array_fill(0, ProjectSectionPayload::MAX_BLOCKS + 1, ['type' => 'paragraphe', 'text' => 'a']);
        $violations = $this->validate(new ProjectSectionPayload('Titre', $blocks));

        $this->assertCount(1, $violations);
        $this->assertSame('blocks', $violations[0]->getPropertyPath());
    }

    private function validate(ProjectSectionPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
