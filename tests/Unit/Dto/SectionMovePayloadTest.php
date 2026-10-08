<?php

namespace App\Tests\Unit\Dto;

use App\Dto\SectionMovePayload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class SectionMovePayloadTest extends TestCase
{
    #[DataProvider('validDirectionProvider')]
    public function testValidDirection(string $direction): void
    {
        $this->assertCount(0, $this->validate(new SectionMovePayload($direction)));
    }

    public static function validDirectionProvider(): iterable
    {
        yield 'haut' => [SectionMovePayload::UP];
        yield 'bas' => [SectionMovePayload::DOWN];
    }

    public function testDirectionIsRequired(): void
    {
        $violations = $this->validate(new SectionMovePayload());

        $this->assertCount(1, $violations);
        $this->assertSame('La direction est obligatoire.', $violations[0]->getMessage());
    }

    public function testUnknownDirectionIsRefused(): void
    {
        $violations = $this->validate(new SectionMovePayload('left'));

        $this->assertCount(1, $violations);
        $this->assertSame('La direction doit être « up » ou « down ».', $violations[0]->getMessage());
    }

    private function validate(SectionMovePayload $payload): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
