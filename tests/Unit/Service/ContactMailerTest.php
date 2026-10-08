<?php

namespace App\Tests\Unit\Service;

use App\Entity\Contact;
use App\Service\ContactMailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Le mailer est remplacé par un mock : aucun e-mail ne part,
 * on récupère l'e-mail construit pour vérifier son contenu.
 */
final class ContactMailerTest extends TestCase
{
    public function testEmailIsSentToTheSiteOwnerWithReplyToTheVisitor(): void
    {
        $email = $this->send($this->createContact());

        $this->assertSame('ne-pas-repondre@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('contact@example.com', $email->getTo()[0]->getAddress());
        // « Répondre » répond directement au visiteur
        $this->assertSame('marie@example.com', $email->getReplyTo()[0]->getAddress());
        $this->assertSame('Marie Dupont', $email->getReplyTo()[0]->getName());
    }

    public function testSubjectStartsWithContact(): void
    {
        $email = $this->send($this->createContact(subject: 'Site vitrine'));

        $this->assertSame('Contact : Site vitrine', $email->getSubject());
    }

    public function testEmptySubjectGetsADefaultSubject(): void
    {
        $email = $this->send($this->createContact(subject: null));

        $this->assertSame('Contact : Nouveau message', $email->getSubject());
    }

    public function testBodyContainsAllTheInformation(): void
    {
        $body = $this->send($this->createContact(phone: '06 12 34 56 78'))->getTextBody();

        $this->assertStringContainsString('Prénom : Marie', $body);
        $this->assertStringContainsString('Nom : Dupont', $body);
        $this->assertStringContainsString('E-mail : marie@example.com', $body);
        $this->assertStringContainsString('Téléphone : 06 12 34 56 78', $body);
        $this->assertStringContainsString('Sujet : Site vitrine', $body);
        $this->assertStringContainsString("Message :\nJ'aimerais un site pour mon activité.", $body);
    }

    public function testMissingOptionalFieldsAreMarkedAsNotProvided(): void
    {
        $body = $this->send($this->createContact(subject: null))->getTextBody();

        $this->assertStringContainsString('Téléphone : non renseigné', $body);
        $this->assertStringContainsString('Sujet : non renseigné', $body);
    }

    /**
     * Sécurité : texte brut uniquement, ce qu'a saisi le visiteur n'est jamais interprété comme du HTML.
     */
    public function testEmailHasNoHtmlPart(): void
    {
        $email = $this->send($this->createContact(message: '<script>alert(1)</script> Bonjour'));

        $this->assertNull($email->getHtmlBody());
        $this->assertStringContainsString('<script>alert(1)</script> Bonjour', $email->getTextBody());
    }

    /**
     * Envoie la demande avec un faux mailer et renvoie l'e-mail qu'il a reçu.
     */
    private function send(Contact $contact): Email
    {
        $sentEmail = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($this->callback(
            static function (Email $email) use (&$sentEmail): bool {
                $sentEmail = $email;

                return true;
            },
        ));

        (new ContactMailer($mailer, 'ne-pas-repondre@example.com', 'contact@example.com'))->send($contact);

        return $sentEmail;
    }

    private function createContact(?string $subject = 'Site vitrine', ?string $phone = null, string $message = "J'aimerais un site pour mon activité."): Contact
    {
        return (new Contact('Marie', 'Dupont', 'marie@example.com', $message))
            ->setPhone($phone)
            ->setSubject($subject);
    }
}
