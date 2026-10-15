<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectStatePayload;
use App\Entity\ProjectState;
use App\Enum\ProjectStateColor;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStateRepository;
use App\Service\PositionMover;
use App\Service\ProjectStateManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ProjectStateManagerTest extends TestCase
{
    /** @var list<ProjectState> statuts déjà en base */
    private array $states = [];

    protected function setUp(): void
    {
        $this->states = [
            new ProjectState('pas_commence', 'Pas commencé', 'Pas commencés', 0, ProjectStateColor::Violet),
            new ProjectState('en_cours', 'En cours', 'En cours', 1, ProjectStateColor::Blue),
            new ProjectState('termine', 'Terminé', 'Terminés', 2, ProjectStateColor::Green),
        ];
    }

    /* ------------------------------------------------------------------
       Ajout
       ------------------------------------------------------------------ */

    public function testCreateCalculatesTheCodeAndAddsTheStateAtTheEnd(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(ProjectState::class));
        $entityManager->expects($this->once())->method('flush');

        $state = $this->createManager($entityManager)->create(new ProjectStatePayload('  En pause ', 'En pause', 'orange'));

        $this->assertSame('en_pause', $state->getCode());
        $this->assertSame('En pause', $state->getLabel());
        $this->assertSame(3, $state->getPosition());
        $this->assertSame(ProjectStateColor::Orange, $state->getColor());
    }

    public function testEmptyPluralLabelTakesTheLabel(): void
    {
        $state = $this->createManager()->create(new ProjectStatePayload('Abandonné', '  ', 'rouge'));

        $this->assertSame('Abandonné', $state->getPluralLabel());
        // Les accents disparaissent du code
        $this->assertSame('abandonne', $state->getCode());
    }

    public function testCodeAlreadyTakenGetsANumber(): void
    {
        // Le statut « En cours » a été renommé : son code en_cours reste pris
        $this->states[1]->setLabel('Démarré');

        $state = $this->createManager()->create(new ProjectStatePayload('En cours', null, 'bleu'));

        $this->assertSame('en_cours_2', $state->getCode());
    }

    public function testLongLabelGivesACodeThatFitsTheColumn(): void
    {
        $state = $this->createManager()->create(new ProjectStatePayload(str_repeat('Attente ', 6), null, 'gris'));

        $this->assertLessThanOrEqual(30, mb_strlen($state->getCode()));
    }

    public function testCreateWithAnExistingLabelIsRefused(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Le statut « Terminé » existe déjà.');

        $this->createManager()->create(new ProjectStatePayload('Terminé', null, 'vert'));
    }

    /* ------------------------------------------------------------------
       Modification
       ------------------------------------------------------------------ */

    public function testUpdateChangesLabelsAndColorButNotTheCode(): void
    {
        $state = $this->states[2];

        $this->createManager()->update($state, new ProjectStatePayload('Fini', 'Finis', 'violet'));

        $this->assertSame('Fini', $state->getLabel());
        $this->assertSame('Finis', $state->getPluralLabel());
        $this->assertSame(ProjectStateColor::Violet, $state->getColor());
        $this->assertSame('termine', $state->getCode());
    }

    public function testUpdateKeepingItsOwnLabelIsAllowed(): void
    {
        $state = $this->states[2];

        $this->createManager()->update($state, new ProjectStatePayload('Terminé', 'Terminés', 'vert'));

        $this->assertSame('Terminé', $state->getLabel());
    }

    public function testUpdateWithTheLabelOfAnotherStateIsRefused(): void
    {
        $this->expectException(\DomainException::class);

        $this->createManager()->update($this->states[2], new ProjectStatePayload('En cours', null, 'vert'));
    }

    /* ------------------------------------------------------------------
       Suppression
       ------------------------------------------------------------------ */

    public function testDeleteAnUnusedState(): void
    {
        $state = $this->states[0];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($state);
        $entityManager->expects($this->once())->method('flush');

        $this->createManager($entityManager)->delete($state);
    }

    public function testDeleteAStateUsedByProjectsIsRefused(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('« En cours » est utilisé par 2 projets : change d\'abord leur statut.');

        $this->createManager($entityManager, projectCount: 2)->delete($this->states[1]);
    }

    public function testMessageForASingleProject(): void
    {
        $this->expectExceptionMessage('« En cours » est utilisé par 1 projet : change d\'abord son statut.');

        $this->createManager(projectCount: 1)->delete($this->states[1]);
    }

    public function testTheLastStateCannotBeDeleted(): void
    {
        $this->states = [$this->states[0]];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Il faut garder au moins un statut');

        $this->createManager($entityManager)->delete($this->states[0]);
    }

    /**
     * @param int $projectCount nombre de projets qui utilisent le statut à supprimer
     */
    public function testMoveUpMakesTheStateTheDefaultOne(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $this->createManager($entityManager)->move($this->states[2], 'up');

        $this->assertSame([0, 2, 1], array_map(static fn (ProjectState $state): int => $state->getPosition(), $this->states));
    }

    public function testMoveDownTheLastStateChangesNothing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $this->createManager($entityManager)->move($this->states[2], 'down');

        $this->assertSame(2, $this->states[2]->getPosition());
    }

    private function createManager(?EntityManagerInterface $entityManager = null, int $projectCount = 0): ProjectStateManager
    {
        $repository = $this->createStub(ProjectStateRepository::class);
        $repository->method('findAllOrdered')->willReturnCallback(fn (): array => $this->states);
        $repository->method('findOneBy')->willReturnCallback(function (array $criteria): ?ProjectState {
            foreach ($this->states as $state) {
                $value = isset($criteria['code']) ? $state->getCode() : $state->getLabel();

                if ($value === ($criteria['code'] ?? $criteria['label'])) {
                    return $state;
                }
            }

            return null;
        });
        $repository->method('getNextPosition')->willReturnCallback(fn (): int => \count($this->states));
        $repository->method('count')->willReturnCallback(fn (): int => \count($this->states));

        $projectRepository = $this->createStub(ProjectRepository::class);
        $projectRepository->method('count')->willReturn($projectCount);

        return new ProjectStateManager(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $repository,
            $projectRepository,
            new AsciiSlugger(),
            new PositionMover(),
        );
    }
}
