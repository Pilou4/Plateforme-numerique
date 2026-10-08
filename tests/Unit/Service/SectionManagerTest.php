<?php

namespace App\Tests\Unit\Service;

use App\Dto\SectionMovePayload;
use App\Dto\SectionPayload;
use App\Entity\ProjectSection;
use App\Service\SectionManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * La base de données est remplacée par un faux EntityManager :
 * on vérifie seulement ce que le service fait sur les sections.
 */
final class SectionManagerTest extends TestCase
{
    public function testAddPutsTheSectionAtTheEnd(): void
    {
        $sections = $this->createSections(3);
        $section = new ProjectSection();
        $sections->add($section);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($section);
        $entityManager->expects($this->once())->method('flush');

        (new SectionManager($entityManager))->add($sections, $section, new SectionPayload('  Installation  ', '  <p>Texte</p>  '));

        $this->assertSame(3, $section->getPosition());
        $this->assertSame('Installation', $section->getTitle());
        $this->assertSame('<p>Texte</p>', $section->getContent());
    }

    public function testAddToAnEmptyPageGivesPositionZero(): void
    {
        $section = new ProjectSection();
        $sections = new ArrayCollection([$section]);

        $this->createManager()->add($sections, $section, new SectionPayload('Titre', 'Contenu'));

        $this->assertSame(0, $section->getPosition());
    }

    public function testUpdateChangesTitleAndContent(): void
    {
        $section = (new ProjectSection())->setTitle('Ancien')->setContent('Ancien');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        (new SectionManager($entityManager))->update($section, new SectionPayload(' Nouveau ', ' Contenu '));

        $this->assertSame('Nouveau', $section->getTitle());
        $this->assertSame('Contenu', $section->getContent());
    }

    public function testDeleteRenumbersTheOtherSections(): void
    {
        $sections = $this->createSections(4);
        [$first, $second, $third, $fourth] = $sections->toArray();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($second);
        $entityManager->expects($this->once())->method('flush');

        (new SectionManager($entityManager))->delete($sections, $second);

        $this->assertCount(3, $sections);
        $this->assertSame([0, 1, 2], [$first->getPosition(), $third->getPosition(), $fourth->getPosition()]);
    }

    public function testMoveUpSwapsWithThePreviousSection(): void
    {
        $sections = $this->createSections(3);
        [$first, $second, $third] = $sections->toArray();

        $this->createManager()->move($sections, $third, SectionMovePayload::UP);

        $this->assertSame([0, 2, 1], [$first->getPosition(), $second->getPosition(), $third->getPosition()]);
    }

    public function testMoveDownSwapsWithTheNextSection(): void
    {
        $sections = $this->createSections(3);
        [$first, $second, $third] = $sections->toArray();

        $this->createManager()->move($sections, $first, SectionMovePayload::DOWN);

        $this->assertSame([1, 0, 2], [$first->getPosition(), $second->getPosition(), $third->getPosition()]);
    }

    public function testMoveUpTheFirstSectionChangesNothing(): void
    {
        $sections = $this->createSections(3);
        [$first, $second, $third] = $sections->toArray();

        // Rien ne change : rien n'est enregistré
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        (new SectionManager($entityManager))->move($sections, $first, SectionMovePayload::UP);

        $this->assertSame([0, 1, 2], [$first->getPosition(), $second->getPosition(), $third->getPosition()]);
    }

    public function testMoveDownTheLastSectionChangesNothing(): void
    {
        $sections = $this->createSections(2);
        [$first, $second] = $sections->toArray();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        (new SectionManager($entityManager))->move($sections, $second, SectionMovePayload::DOWN);

        $this->assertSame([0, 1], [$first->getPosition(), $second->getPosition()]);
    }

    public function testMoveFollowsThePositionsNotTheListOrder(): void
    {
        // Liste dans le désordre : c'est la position qui compte
        $top = (new ProjectSection())->setTitle('Haut')->setPosition(0);
        $bottom = (new ProjectSection())->setTitle('Bas')->setPosition(1);
        $sections = new ArrayCollection([$bottom, $top]);

        $this->createManager()->move($sections, $bottom, SectionMovePayload::UP);

        $this->assertSame(0, $bottom->getPosition());
        $this->assertSame(1, $top->getPosition());
    }

    /**
     * Faux EntityManager quand on ne vérifie pas ses appels.
     */
    private function createManager(): SectionManager
    {
        return new SectionManager($this->createStub(EntityManagerInterface::class));
    }

    /**
     * @return ArrayCollection<int, ProjectSection> sections aux positions 0, 1, 2…
     */
    private function createSections(int $count): ArrayCollection
    {
        $sections = new ArrayCollection();

        for ($position = 0; $position < $count; ++$position) {
            $sections->add((new ProjectSection())->setTitle('Section '.$position)->setContent('Contenu')->setPosition($position));
        }

        return $sections;
    }
}
