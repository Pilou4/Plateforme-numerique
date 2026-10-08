<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectStepPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Ce DTO sert à la création (POST, groupe « create ») et à la modification (PATCH, groupe par défaut).
 */
final class ProjectStepPayloadTest extends TestCase
{
    private const array CREATE_GROUPS = ['Default', ProjectStepPayload::GROUP_CREATE];

    public function testCreateWithATitle(): void
    {
        $this->assertCount(0, $this->validate(new ProjectStepPayload(title: 'Page d\'accueil'), self::CREATE_GROUPS));
    }

    public function testCreateWithoutTitleIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepPayload(), self::CREATE_GROUPS);

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
        $this->assertSame('Le titre est obligatoire.', $violations[0]->getMessage());
    }

    public function testUpdateWithoutTitleIsAllowed(): void
    {
        // En modification, un champ absent n'est pas modifié
        $this->assertCount(0, $this->validate(new ProjectStepPayload(timeSpent: 30)));
    }

    public function testUpdateWithAnEmptyTitleIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepPayload(title: ''));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
    }

    public function testTitleTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepPayload(title: str_repeat('a', 256)));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
    }

    public function testNegativeTimeIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepPayload(timeSpent: -5));

        $this->assertCount(1, $violations);
        $this->assertSame('Le temps passé ne peut pas être négatif.', $violations[0]->getMessage());
    }

    public function testTimeTooBigIsRefused(): void
    {
        $violations = $this->validate(new ProjectStepPayload(timeSpent: 1_000_001));

        $this->assertCount(1, $violations);
        $this->assertSame('Le temps passé est trop grand.', $violations[0]->getMessage());
    }

    /**
     * @param list<string>|null $groups null = groupe par défaut (modification)
     */
    private function validate(ProjectStepPayload $payload, ?array $groups = null): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload, null, $groups);
    }
}
