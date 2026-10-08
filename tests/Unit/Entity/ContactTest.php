<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Contact;
use PHPUnit\Framework\TestCase;

final class ContactTest extends TestCase
{
    public function testNewContactHasItsDateAndNoOptionalFields(): void
    {
        $contact = new Contact('Marie', 'Dupont', 'marie@example.com', 'Bonjour');

        $this->assertNotNull($contact->getCreatedAt());
        $this->assertNull($contact->getPhone());
        $this->assertNull($contact->getSubject());
    }

    public function testFullName(): void
    {
        $contact = new Contact('Marie', 'Dupont', 'marie@example.com', 'Bonjour');

        $this->assertSame('Marie Dupont', $contact->getFullName());
    }
}
