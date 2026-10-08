<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectStepOrderPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ProjectStepOrderPayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $this->assertCount(0, $this->validate(new ProjectStepOrderPayload([3, 1, 2])));
    }

    public function testEmptyListIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepOrderPayload([]));

        $this->assertCount(1, $violations);
        $this->assertSame('La liste des tâches est vide.', $violations[0]->getMessage());
    }

    public function testDuplicateIdIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepOrderPayload([1, 2, 1]));

        $this->assertCount(1, $violations);
        $this->assertSame('Une tâche apparaît plusieurs fois.', $violations[0]->getMessage());
    }

    public function testNegativeIdIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepOrderPayload([1, -2]));

        $this->assertCount(1, $violations);
        $this->assertSame('ids[1]', $violations[0]->getPropertyPath());
    }

    public function testTextIdIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepOrderPayload([1, 'deux']));

        $this->assertGreaterThan(0, \count($violations));
        $this->assertSame('ids[1]', $violations[0]->getPropertyPath());
    }

    private function validate(ProjectStepOrderPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
