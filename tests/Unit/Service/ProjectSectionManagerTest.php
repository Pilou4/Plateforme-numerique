<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectSectionPayload;
use App\Entity\Project;
use App\Entity\ProjectSection;
use App\Repository\ProjectFileRepository;
use App\Service\ProjectSectionBlockBuilder;
use App\Service\ProjectSectionManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectSectionManagerTest extends TestCase
{
    private Project $project;

    protected function setUp(): void
    {
        $this->project = (new Project())->setName('Plateforme')->setSlug('plateforme');
    }

    public function testCreateAddsTheSectionAtTheEndWithItsBlocks(): void
    {
        $this->addSections('Contexte');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(ProjectSection::class));
        $entityManager->expects($this->once())->method('flush');

        $section = $this->createManager($entityManager)->create($this->project, new ProjectSectionPayload('  Base de données ', [
            ['type' => 'paragraphe', 'text' => 'Texte'],
            ['type' => 'tags', 'tags' => ['MySQL']],
        ]));

        $this->assertSame('Base de données', $section->getTitle());
        $this->assertSame(1, $section->getPosition());
        $this->assertSame($this->project, $section->getProject());
        $this->assertCount(2, $section->getBlocks());
        $this->assertTrue($this->project->getSections()->contains($section));
    }

    public function testCreateWithARefusedBlockSavesNothing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        try {
            $this->createManager($entityManager)->create($this->project, new ProjectSectionPayload('Titre', [['type' => 'liste', 'items' => []]]));
            $this->fail('Le bloc vide aurait dû être refusé.');
        } catch (\DomainException) {
        }

        $this->assertCount(0, $this->project->getSections());
    }

    public function testUpdateChangesTitleAndBlocks(): void
    {
        [$section] = $this->addSections('Ancien titre');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $this->createManager($entityManager)->update($section, new ProjectSectionPayload(' Nouveau titre ', [['type' => 'sous_titre', 'text' => 'Sous-titre']]));

        $this->assertSame('Nouveau titre', $section->getTitle());
        $this->assertSame('Sous-titre', $section->getBlocks()->first()->getSubtitle());
    }

    public function testUpdateWithARefusedBlockChangesNothing(): void
    {
        [$section] = $this->addSections('Titre');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        try {
            $this->createManager($entityManager)->update($section, new ProjectSectionPayload('Autre titre', [['type' => 'inconnu']]));
            $this->fail('Le bloc inconnu aurait dû être refusé.');
        } catch (\DomainException) {
        }

        $this->assertSame('Titre', $section->getTitle());
    }

    public function testDeleteRenumbersTheOtherSections(): void
    {
        [$first, $second, $third] = $this->addSections('A', 'B', 'C');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($second);
        $entityManager->expects($this->once())->method('flush');

        $this->createManager($entityManager)->delete($this->project, $second);

        $this->assertFalse($this->project->getSections()->contains($second));
        $this->assertSame(0, $first->getPosition());
        $this->assertSame(1, $third->getPosition());
    }

    public function testMoveSwapsWithTheNeighbour(): void
    {
        [$first, $second] = $this->addSections('A', 'B', 'C');

        $this->createManager()->move($this->project, $second, 'up');

        $this->assertSame(0, $second->getPosition());
        $this->assertSame(1, $first->getPosition());
    }

    public function testMoveDownTheLastSectionChangesNothing(): void
    {
        [, , $third] = $this->addSections('A', 'B', 'C');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $this->createManager($entityManager)->move($this->project, $third, 'down');

        $this->assertSame(2, $third->getPosition());
    }

    /**
     * @return list<ProjectSection>
     */
    private function addSections(string ...$titles): array
    {
        $sections = [];

        foreach ($titles as $position => $title) {
            $section = (new ProjectSection())->setTitle($title)->setPosition($position)->setProject($this->project);
            $this->project->addSection($section);
            $sections[] = $section;
        }

        return $sections;
    }

    private function createManager(?EntityManagerInterface $entityManager = null): ProjectSectionManager
    {
        return new ProjectSectionManager(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            new ProjectSectionBlockBuilder($this->createStub(ProjectFileRepository::class)),
        );
    }
}
