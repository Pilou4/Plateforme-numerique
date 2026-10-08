<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ContactMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

final class ContactMessageTest extends TestCase
{
    public function testValidMessage(): void
    {
        $this->assertCount(0, $this->validate($this->createMessage()));
    }

    public function testPhoneAndSubjectAreOptional(): void
    {
        $this->assertCount(0, $this->validate($this->createMessage(phone: null, subject: null)));
    }

    #[DataProvider('requiredFieldProvider')]
    public function testRequiredField(string $field, string $expectedMessage): void
    {
        $message = $this->createMessage();
        // Que des espaces : refusé comme un champ vide
        $message->{$field} = '   ';

        $violations = $this->validate($message);

        $this->assertCount(1, $violations);
        $this->assertSame($field, $violations[0]->getPropertyPath());
        $this->assertSame($expectedMessage, $violations[0]->getMessage());
    }

    public static function requiredFieldProvider(): iterable
    {
        yield 'prénom' => ['firstName', 'Le prénom est obligatoire.'];
        yield 'nom' => ['lastName', 'Le nom est obligatoire.'];
        yield 'e-mail' => ['email', 'L\'adresse e-mail est obligatoire.'];
        yield 'message' => ['message', 'Le message est obligatoire.'];
    }

    public function testInvalidEmailIsRefused(): void
    {
        $violations = $this->validate($this->createMessage(email: 'marie@'));

        $this->assertCount(1, $violations);
        $this->assertSame('L\'adresse e-mail n\'est pas valide.', $violations[0]->getMessage());
    }

    #[DataProvider('validPhoneProvider')]
    public function testValidPhone(string $phone): void
    {
        $this->assertCount(0, $this->validate($this->createMessage(phone: $phone)));
    }

    public static function validPhoneProvider(): iterable
    {
        yield 'avec espaces' => ['06 12 34 56 78'];
        yield 'avec points' => ['06.12.34.56.78'];
        yield 'international' => ['+33 6 12 34 56 78'];
    }

    #[DataProvider('invalidPhoneProvider')]
    public function testInvalidPhone(string $phone): void
    {
        $violations = $this->validate($this->createMessage(phone: $phone));

        $this->assertCount(1, $violations);
        $this->assertSame('Le numéro de téléphone n\'est pas valide.', $violations[0]->getMessage());
    }

    public static function invalidPhoneProvider(): iterable
    {
        yield 'lettres' => ['appelez-moi'];
        yield 'trop court' => ['0612'];
    }

    public function testMessageTooShortIsRefused(): void
    {
        $violations = $this->validate($this->createMessage(message: 'Salut'));

        $this->assertCount(1, $violations);
        $this->assertSame('message', $violations[0]->getPropertyPath());
    }

    public function testMessageTooLongIsRefused(): void
    {
        $violations = $this->validate($this->createMessage(message: str_repeat('a', 5001)));

        $this->assertCount(1, $violations);
        $this->assertSame('message', $violations[0]->getPropertyPath());
    }

    private function createMessage(
        string $email = 'marie@example.com',
        ?string $phone = '06 12 34 56 78',
        ?string $subject = 'Site vitrine',
        string $message = 'J\'aimerais un site pour mon activité.',
    ): ContactMessage {
        $contactMessage = new ContactMessage();
        $contactMessage->firstName = 'Marie';
        $contactMessage->lastName = 'Dupont';
        $contactMessage->email = $email;
        $contactMessage->phone = $phone;
        $contactMessage->subject = $subject;
        $contactMessage->message = $message;

        return $contactMessage;
    }

    private function validate(ContactMessage $message): ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($message);
    }
}
