<?php

namespace App\Tests\Unit\Dto;

use App\Dto\SectionPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class SectionPayloadTest extends TestCase
{
    public function testValidPayload(): void
    {
        $this->assertCount(0, $this->validate(new SectionPayload('Installation', '<p>Texte</p>')));
    }

    public function testTitleAndContentAreRequired(): void
    {
        $violations = $this->validate(new SectionPayload());

        $this->assertCount(2, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
        $this->assertSame('content', $violations[1]->getPropertyPath());
    }

    public function testTitleTooLongIsRefused(): void
    {
        $violations = $this->validate(new SectionPayload(str_repeat('a', 256), 'Contenu'));

        $this->assertCount(1, $violations);
        $this->assertSame('title', $violations[0]->getPropertyPath());
    }

    private function validate(SectionPayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
