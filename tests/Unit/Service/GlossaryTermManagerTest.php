<?php

namespace App\Tests\Unit\Service;

use App\Dto\GlossaryTermPayload;
use App\Entity\GlossaryTerm;
use App\Repository\GlossaryTermRepository;
use App\Service\GlossaryTermManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class GlossaryTermManagerTest extends TestCase
{
    public function testCreateSavesTheTerm(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(GlossaryTerm::class));
        $entityManager->expects($this->once())->method('flush');

        $term = (new GlossaryTermManager($entityManager, $this->createRepository()))
            ->create(new GlossaryTermPayload('  Docker  ', '  <p>Outil de conteneurs.</p>  '));

        $this->assertSame('Docker', $term->getTerm());
        $this->assertSame('<p>Outil de conteneurs.</p>', $term->getDefinition());
    }

    public function testCreateWithAnExistingTermIsRefused(): void
    {
        $existing = (new GlossaryTerm())->setTerm('Docker')->setDefinition('Définition');
        $manager = new GlossaryTermManager($this->createStub(EntityManagerInterface::class), $this->createRepository($existing));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Le terme « Docker » existe déjà dans le glossaire.');

        $manager->create(new GlossaryTermPayload('docker', 'Autre définition'));
    }

    public function testUpdateChangesTheTermAndSetsTheUpdateDate(): void
    {
        $term = (new GlossaryTerm())->setTerm('Docker')->setDefinition('Ancienne');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        // Le repository trouve le terme lui-même : ce n'est pas un doublon
        (new GlossaryTermManager($entityManager, $this->createRepository($term)))
            ->update($term, new GlossaryTermPayload('Docker', 'Nouvelle'));

        $this->assertSame('Nouvelle', $term->getDefinition());
        $this->assertNotNull($term->getUpdatedAt());
    }

    public function testDeleteRemovesTheTerm(): void
    {
        $term = new GlossaryTerm();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($term);
        $entityManager->expects($this->once())->method('flush');

        (new GlossaryTermManager($entityManager, $this->createRepository()))->delete($term);
    }

    /**
     * @param GlossaryTerm|null $existing ce que findOneBy() renvoie
     */
    private function createRepository(?GlossaryTerm $existing = null): GlossaryTermRepository
    {
        $repository = $this->createStub(GlossaryTermRepository::class);
        $repository->method('findOneBy')->willReturn($existing);

        return $repository;
    }
}
