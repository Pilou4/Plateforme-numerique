<?php

namespace App\Tests\Unit\Service;

use App\Dto\ContactMessage;
use App\Entity\Contact;
use App\Service\ContactManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ContactManagerTest extends TestCase
{
    public function testCreateSavesTheRequest(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Contact::class));
        $entityManager->expects($this->once())->method('flush');

        $contact = (new ContactManager($entityManager))->create($this->createMessage());

        $this->assertSame('Marie', $contact->getFirstName());
        $this->assertSame('Dupont', $contact->getLastName());
        $this->assertSame('marie@example.com', $contact->getEmail());
        $this->assertSame('06 12 34 56 78', $contact->getPhone());
        $this->assertSame('Site vitrine', $contact->getSubject());
        $this->assertSame("Bonjour,\nj'aimerais un site.", $contact->getMessage());
    }

    public function testEmptyOptionalFieldsAreSavedAsNull(): void
    {
        $message = $this->createMessage();
        $message->phone = '   ';
        $message->subject = null;

        $contact = $this->createManager()->create($message);

        $this->assertNull($contact->getPhone());
        $this->assertNull($contact->getSubject());
    }

    /**
     * Sécurité : pas de retour à la ligne dans les champs courts (le sujet sert de sujet d'e-mail).
     * Le message, lui, garde ses retours à la ligne.
     */
    public function testLineBreaksAreRemovedExceptInTheMessage(): void
    {
        $message = $this->createMessage();
        $message->firstName = "  Marie\n";
        $message->subject = "Bonjour\r\nBcc: pirate@example.com";

        $contact = $this->createManager()->create($message);

        $this->assertSame('Marie', $contact->getFirstName());
        $this->assertSame('Bonjour Bcc: pirate@example.com', $contact->getSubject());
        $this->assertStringContainsString("\n", $contact->getMessage());
    }

    private function createManager(): ContactManager
    {
        return new ContactManager($this->createStub(EntityManagerInterface::class));
    }

    private function createMessage(): ContactMessage
    {
        $message = new ContactMessage();
        $message->firstName = 'Marie';
        $message->lastName = 'Dupont';
        $message->email = 'marie@example.com';
        $message->phone = '06 12 34 56 78';
        $message->subject = 'Site vitrine';
        $message->message = "  Bonjour,\nj'aimerais un site.  ";

        return $message;
    }
}
