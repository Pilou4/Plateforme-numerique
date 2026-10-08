<?php

namespace App\Service;

use App\Entity\Contact;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Envoie par e-mail une demande du formulaire de contact (déjà enregistrée par ContactManager).
 *
 * - L'e-mail est en texte brut : rien de ce qu'a saisi le visiteur ne peut être interprété comme du HTML.
 * - « Répondre » dans la boîte mail répond directement au visiteur (Reply-To).
 * - Les adresses d'envoi et de réception sont dans .env.local (CONTACT_EMAIL_FROM, CONTACT_EMAIL_TO).
 */
final class ContactMailer
{
    private const string SENDER_NAME = 'Plateforme Numérique';
    private const string DEFAULT_SUBJECT = 'Nouveau message';
    private const string NOT_PROVIDED = 'non renseigné';

    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(CONTACT_EMAIL_FROM)%')]
        private readonly string $senderAddress,
        #[Autowire('%env(CONTACT_EMAIL_TO)%')]
        private readonly string $recipientAddress,
    ) {
    }

    /**
     * @throws TransportExceptionInterface si l'e-mail n'a pas pu partir
     */
    public function send(Contact $contact): void
    {
        $email = (new Email())
            ->from(new Address($this->senderAddress, self::SENDER_NAME))
            ->to($this->recipientAddress)
            ->replyTo(new Address($contact->getEmail(), $contact->getFullName()))
            ->subject('Contact : '.($contact->getSubject() ?? self::DEFAULT_SUBJECT))
            ->text($this->buildBody($contact));

        $this->mailer->send($email);
    }

    private function buildBody(Contact $contact): string
    {
        return implode("\n", [
            'Nouveau message envoyé depuis le formulaire de contact du site.',
            '',
            'Prénom : '.$contact->getFirstName(),
            'Nom : '.$contact->getLastName(),
            'E-mail : '.$contact->getEmail(),
            'Téléphone : '.($contact->getPhone() ?? self::NOT_PROVIDED),
            'Sujet : '.($contact->getSubject() ?? self::NOT_PROVIDED),
            'Date : '.$contact->getCreatedAt()->format('d/m/Y à H:i'),
            '',
            'Message :',
            $contact->getMessage(),
        ]);
    }
}
