<?php

namespace App\Tests\Unit\Service;

use App\Entity\ProjectCategory;
use App\Service\PositionMover;
use PHPUnit\Framework\TestCase;

final class PositionMoverTest extends TestCase
{
    /** @var list<ProjectCategory> */
    private array $items;

    protected function setUp(): void
    {
        $this->items = [new ProjectCategory('A', 0), new ProjectCategory('B', 1), new ProjectCategory('C', 2)];
    }

    public function testMoveUpSwapsWithThePreviousItem(): void
    {
        $this->assertTrue((new PositionMover())->move($this->items, $this->items[2], 'up'));

        $this->assertSame([0, 2, 1], $this->positions());
    }

    public function testMoveDownSwapsWithTheNextItem(): void
    {
        $this->assertTrue((new PositionMover())->move($this->items, $this->items[0], 'down'));

        $this->assertSame([1, 0, 2], $this->positions());
    }

    public function testPositionsAreRenumberedWithoutGaps(): void
    {
        // Positions avec des trous (un élément supprimé entre-temps)
        $this->items[1]->setPosition(5);
        $this->items[2]->setPosition(9);

        (new PositionMover())->move($this->items, $this->items[1], 'down');

        $this->assertSame([0, 2, 1], $this->positions());
    }

    public function testFirstCannotGoUpAndLastCannotGoDown(): void
    {
        $mover = new PositionMover();

        $this->assertFalse($mover->move($this->items, $this->items[0], 'up'));
        $this->assertFalse($mover->move($this->items, $this->items[2], 'down'));
        $this->assertSame([0, 1, 2], $this->positions());
    }

    public function testItemOutsideTheListChangesNothing(): void
    {
        $this->assertFalse((new PositionMover())->move($this->items, new ProjectCategory('Autre', 0), 'up'));
    }

    /**
     * @return list<int>
     */
    private function positions(): array
    {
        return array_map(static fn (ProjectCategory $item): int => $item->getPosition(), $this->items);
    }
}
