<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ProjectPayload;
use App\Entity\ProjectState;
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

    public function testKnownStateAndCategoryAreAccepted(): void
    {
        $this->assertCount(0, $this->validate(new ProjectPayload('Plateforme', null, ProjectState::CODE_DONE, '3')));
    }

    public function testEmptyCategoryMeansNoCategory(): void
    {
        $this->assertCount(0, $this->validate(new ProjectPayload('Plateforme', null, null, '')));
    }

    /**
     * Les statuts se gèrent depuis l'app : un code inconnu ici peut exister en base.
     * Son existence est vérifiée par ProjectManager (voir ProjectManagerTest::testUnknownStateIsRefused).
     */
    public function testAnyStateCodeIsAcceptedByThePayload(): void
    {
        $this->assertCount(0, $this->validate(new ProjectPayload('Plateforme', null, 'en_pause')));
    }

    public function testStateCodeTooLongIsRefused(): void
    {
        $violations = $this->validate(new ProjectPayload('Plateforme', null, str_repeat('a', 31)));

        $this->assertCount(1, $violations);
        $this->assertSame('state', $violations[0]->getPropertyPath());
    }

    public function testCategoryMustBeANumber(): void
    {
        $violations = $this->validate(new ProjectPayload('Plateforme', null, null, 'jeu'));

        $this->assertCount(1, $violations);
        $this->assertSame('category', $violations[0]->getPropertyPath());
    }

    private function validate(ProjectPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
