<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectFavoritePayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ProjectFavoritePayloadTest extends TestCase
{
    public function testTrueAndFalseAreAccepted(): void
    {
        $this->assertCount(0, $this->validate(new ProjectFavoritePayload(true)));
        $this->assertCount(0, $this->validate(new ProjectFavoritePayload(false)));
    }

    public function testMissingValueIsRefused(): void
    {
        $violations = $this->validate(new ProjectFavoritePayload());

        $this->assertCount(1, $violations);
        $this->assertSame('favorite', $violations[0]->getPropertyPath());
    }

    private function validate(ProjectFavoritePayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
