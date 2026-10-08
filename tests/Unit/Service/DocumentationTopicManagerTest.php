<?php

namespace App\Tests\Unit\Service;

use App\Dto\DocumentationTopicPayload;
use App\Entity\DocumentationSection;
use App\Entity\DocumentationTopic;
use App\Repository\DocumentationTopicRepository;
use App\Service\DocumentationTopicManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Le slugger est le vrai (AsciiSlugger) : il ne touche pas à la base,
 * pas besoin de le remplacer.
 */
final class DocumentationTopicManagerTest extends TestCase
{
    public function testCreateSavesTheTopicAtTheEnd(): void
    {
        $repository = $this->createStub(DocumentationTopicRepository::class);
        $repository->method('count')->willReturn(4);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(DocumentationTopic::class));
        $entityManager->expects($this->once())->method('flush');

        $topic = (new DocumentationTopicManager($entityManager, $repository, new AsciiSlugger()))
            ->create(new DocumentationTopicPayload('  Git et GitHub  ', '  Versionner son code  '));

        $this->assertSame('Git et GitHub', $topic->getTitle());
        $this->assertSame('git-et-github', $topic->getSlug());
        $this->assertSame('Versionner son code', $topic->getSummary());
        $this->assertSame(4, $topic->getPosition());
    }

    public function testEmptySummaryIsSavedAsNull(): void
    {
        $topic = $this->createManager()->create(new DocumentationTopicPayload('Docker', '   '));

        $this->assertNull($topic->getSummary());
    }

    public function testSlugWithoutAccents(): void
    {
        $topic = $this->createManager()->create(new DocumentationTopicPayload('Sécurité des données'));

        $this->assertSame('securite-des-donnees', $topic->getSlug());
    }

    public function testSlugAlreadyTakenGetsANumber(): void
    {
        // « docker » et « docker-2 » sont déjà pris par d'autres tutoriels
        $topic = $this->createManager(['docker', 'docker-2'])->create(new DocumentationTopicPayload('Docker'));

        $this->assertSame('docker-3', $topic->getSlug());
    }

    public function testReservedSlugGetsANumber(): void
    {
        // /app/documentation/glossaire est déjà la page du glossaire
        $topic = $this->createManager()->create(new DocumentationTopicPayload('Glossaire'));

        $this->assertSame('glossaire-2', $topic->getSlug());
    }

    public function testUpdateKeepsItsOwnSlug(): void
    {
        $topic = (new DocumentationTopic())->setTitle('Docker')->setSlug('docker');

        $repository = $this->createStub(DocumentationTopicRepository::class);
        $repository->method('findOneBy')->willReturn($topic);

        (new DocumentationTopicManager($this->createStub(EntityManagerInterface::class), $repository, new AsciiSlugger()))
            ->update($topic, new DocumentationTopicPayload('Docker'));

        $this->assertSame('docker', $topic->getSlug());
    }

    public function testUpdateChangesTheSlugWithTheTitle(): void
    {
        $topic = (new DocumentationTopic())->setTitle('Docker')->setSlug('docker');

        $this->createManager()->update($topic, new DocumentationTopicPayload('Docker Compose'));

        $this->assertSame('docker-compose', $topic->getSlug());
    }

    public function testDeleteRemovesTheTopicAndItsSections(): void
    {
        $topic = new DocumentationTopic();
        $topic->addSection(new DocumentationSection());
        $topic->addSection(new DocumentationSection());

        // 2 sections + le tutoriel
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(3))->method('remove');
        $entityManager->expects($this->once())->method('flush');

        (new DocumentationTopicManager($entityManager, $this->createStub(DocumentationTopicRepository::class), new AsciiSlugger()))
            ->delete($topic);
    }

    /**
     * @param list<string> $takenSlugs slugs déjà utilisés par d'autres tutoriels
     */
    private function createManager(array $takenSlugs = []): DocumentationTopicManager
    {
        $repository = $this->createStub(DocumentationTopicRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?DocumentationTopic => \in_array($criteria['slug'], $takenSlugs, true)
                ? (new DocumentationTopic())->setSlug($criteria['slug'])
                : null,
        );

        return new DocumentationTopicManager($this->createStub(EntityManagerInterface::class), $repository, new AsciiSlugger());
    }
}
