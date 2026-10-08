<?php

namespace App\Service;

use App\Dto\ContactMessage;
use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistre dans la table contact une demande envoyée avec le formulaire de contact.
 */
final class ContactManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Les textes sont nettoyés : espaces en trop retirés, retours à la ligne supprimés
     * partout sauf dans le message, champs facultatifs vides enregistrés à NULL.
     */
    public function create(ContactMessage $message): Contact
    {
        $contact = (new Contact(
            $this->toSingleLine($message->firstName),
            $this->toSingleLine($message->lastName),
            $this->toSingleLine($message->email),
            trim((string) $message->message),
        ))
            ->setPhone($this->toSingleLineOrNull($message->phone))
            ->setSubject($this->toSingleLineOrNull($message->subject));

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $contact;
    }

    private function toSingleLine(?string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
    }

    private function toSingleLineOrNull(?string $text): ?string
    {
        $text = $this->toSingleLine($text);

        return '' === $text ? null : $text;
    }
}
