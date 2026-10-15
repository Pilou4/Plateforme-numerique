<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectCategoryPayload;
use App\Entity\ProjectCategory;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use App\Service\PositionMover;
use App\Service\ProjectCategoryManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectCategoryManagerTest extends TestCase
{
    /** @var array<string, ProjectCategory> catégories déjà en base, par nom */
    private array $categories = [];

    protected function setUp(): void
    {
        $this->categories = ['Jeu' => new ProjectCategory('Jeu', 0), 'Site vitrine' => new ProjectCategory('Site vitrine', 1)];
    }

    public function testCreateAddsTheCategoryAtTheEnd(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(ProjectCategory::class));
        $entityManager->expects($this->once())->method('flush');

        $category = $this->createManager($entityManager)->create(new ProjectCategoryPayload('  Application mobile  '));

        $this->assertSame('Application mobile', $category->getName());
        $this->assertSame(2, $category->getPosition());
    }

    public function testCreateWithAnExistingNameIsRefused(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('La catégorie « Jeu » existe déjà.');

        $this->createManager()->create(new ProjectCategoryPayload('Jeu'));
    }

    public function testUpdateRenamesTheCategory(): void
    {
        $category = $this->categories['Jeu'];

        $this->createManager()->update($category, new ProjectCategoryPayload('Jeu vidéo'));

        $this->assertSame('Jeu vidéo', $category->getName());
    }

    public function testUpdateKeepingItsOwnNameIsAllowed(): void
    {
        $category = $this->categories['Jeu'];

        $this->createManager()->update($category, new ProjectCategoryPayload('Jeu'));

        $this->assertSame('Jeu', $category->getName());
    }

    public function testUpdateWithTheNameOfAnotherCategoryIsRefused(): void
    {
        $this->expectException(\DomainException::class);

        $this->createManager()->update($this->categories['Jeu'], new ProjectCategoryPayload('Site vitrine'));
    }

    /**
     * La catégorie est supprimée même si des projets l'utilisent : ils n'ont plus de catégorie.
     */
    public function testDeleteReturnsTheNumberOfProjectsThatLoseTheirCategory(): void
    {
        $category = $this->categories['Jeu'];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($category);
        $entityManager->expects($this->once())->method('flush');

        $this->assertSame(3, $this->createManager($entityManager, projectCount: 3)->delete($category));
    }

    public function testMoveDownSwapsWithTheNextCategory(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $this->createManager($entityManager)->move($this->categories['Jeu'], 'down');

        $this->assertSame(1, $this->categories['Jeu']->getPosition());
        $this->assertSame(0, $this->categories['Site vitrine']->getPosition());
    }

    public function testMoveUpTheFirstCategoryChangesNothing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $this->createManager($entityManager)->move($this->categories['Jeu'], 'up');

        $this->assertSame(0, $this->categories['Jeu']->getPosition());
    }

    private function createManager(?EntityManagerInterface $entityManager = null, int $projectCount = 0): ProjectCategoryManager
    {
        $repository = $this->createStub(ProjectCategoryRepository::class);
        $repository->method('findOneBy')->willReturnCallback(fn (array $criteria): ?ProjectCategory => $this->categories[$criteria['name']] ?? null);
        $repository->method('getNextPosition')->willReturn(\count($this->categories));
        $repository->method('findAllOrdered')->willReturnCallback(fn (): array => array_values($this->categories));

        $projectRepository = $this->createStub(ProjectRepository::class);
        $projectRepository->method('count')->willReturn($projectCount);

        return new ProjectCategoryManager($entityManager ?? $this->createStub(EntityManagerInterface::class), $repository, $projectRepository, new PositionMover());
    }
}
