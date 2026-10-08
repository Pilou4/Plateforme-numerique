<?php

namespace App\Tests\Unit\Dto;

use App\Dto\DocumentationTopicPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class DocumentationTopicPayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $this->assertCount(0, $this->validate(new DocumentationTopicPayload('Docker', 'Conteneurs')));
    }

    public function testTitleIsRequired(): void
    {
        $violations = $this->validate(new DocumentationTopicPayload(''));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
        $this->assertSame('Le titre est obligatoire.', $violations[0]->getMessage());
    }

    public function testTitleTooLongIsRefused(): void
    {
        $violations = $this->validate(new DocumentationTopicPayload(str_repeat('a', 256)));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
    }

    public function testSummaryTooLongIsRefused(): void
    {
        $violations = $this->validate(new DocumentationTopicPayload('Docker', str_repeat('a', 256)));

        $this->assertCount(1, $violations);
        $this->assertSame('summary', $violations[0]->getPropertyPath());
    }

    private function validate(DocumentationTopicPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
