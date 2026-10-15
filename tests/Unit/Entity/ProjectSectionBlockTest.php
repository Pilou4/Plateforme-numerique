<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ProjectFile;
use App\Entity\ProjectSection;
use App\Entity\ProjectSectionBlock;
use App\Enum\ProjectSectionBlockType;
use PHPUnit\Framework\TestCase;

final class ProjectSectionBlockTest extends TestCase
{
    public function testParagraphsAreNumberedInOrder(): void
    {
        $block = (new ProjectSectionBlock(ProjectSectionBlockType::Paragraph))
            ->addParagraph('Première ligne')
            ->addParagraph('Deuxième ligne');

        $paragraphs = $block->getParagraphs()->toArray();

        $this->assertSame([0, 1], array_map(static fn ($paragraph): int => $paragraph->getPosition(), $paragraphs));
        $this->assertSame('Deuxième ligne', $paragraphs[1]->getText());
        $this->assertSame($block, $paragraphs[0]->getBlock());
    }

    public function testListIsUnorderedByDefault(): void
    {
        $block = new ProjectSectionBlock(ProjectSectionBlockType::List);
        $this->assertFalse($block->isOrdered());

        $block->setOrdered(true)->addListItem('Docker')->addListItem('Git');

        $this->assertTrue($block->isOrdered());
        $this->assertSame(['Docker', 'Git'], array_map(static fn ($item): string => $item->getText(), $block->getListItems()->toArray()));
        $this->assertSame(1, $block->getListItems()[1]->getPosition());
    }

    public function testTableRowHasOneCellPerColumnInColumnOrder(): void
    {
        $block = new ProjectSectionBlock(ProjectSectionBlockType::Table);
        $tableColumn = $block->addTableColumn('Table');
        $roleColumn = $block->addTableColumn('Rôle');

        $row = $block->addTableRow(['project', 'Un projet']);
        $cells = $row->getOrderedCells();

        $this->assertSame(1, $roleColumn->getPosition());
        $this->assertSame(0, $row->getPosition());
        $this->assertCount(2, $cells);
        $this->assertSame($tableColumn, $cells[0]->getColumn());
        $this->assertSame('project', $cells[0]->getText());
        $this->assertSame($roleColumn, $cells[1]->getColumn());
        $this->assertSame('Un projet', $cells[1]->getText());
        $this->assertSame($row, $cells[1]->getRow());
    }

    public function testTableRowWithTheWrongNumberOfCellsIsRefused(): void
    {
        $block = new ProjectSectionBlock(ProjectSectionBlockType::Table);
        $block->addTableColumn('A');
        $block->addTableColumn('B');

        $this->expectException(\LogicException::class);

        $block->addTableRow(['seulement une case']);
    }

    public function testTagsAndDefinitions(): void
    {
        $block = (new ProjectSectionBlock(ProjectSectionBlockType::Tags))->addTag('MySQL')->addTag('Doctrine');
        $this->assertSame(['MySQL', 'Doctrine'], array_map(static fn ($tag): string => $tag->getLabel(), $block->getTags()->toArray()));

        $definitions = (new ProjectSectionBlock(ProjectSectionBlockType::Definitions))->addDefinition('Entité', 'Une classe PHP.');
        $definition = $definitions->getDefinitions()[0];
        $this->assertSame('Entité', $definition->getTerm());
        $this->assertSame('Une classe PHP.', $definition->getDefinition());
    }

    public function testFilesKeepTheirOrderAndCaption(): void
    {
        $first = new ProjectFile();
        $second = new ProjectFile();
        $block = (new ProjectSectionBlock(ProjectSectionBlockType::Images))
            ->addFile($first, 'Schéma')
            ->addFile($second);

        $links = $block->getFiles()->toArray();

        $this->assertSame($first, $links[0]->getFile());
        $this->assertSame('Schéma', $links[0]->getCaption());
        $this->assertSame($second, $links[1]->getFile());
        $this->assertNull($links[1]->getCaption());
        $this->assertSame(1, $links[1]->getPosition());
        $this->assertSame($block, $links[1]->getBlock());
    }

    public function testSubtitle(): void
    {
        $block = (new ProjectSectionBlock(ProjectSectionBlockType::Subtitle))->setSubtitle('Les tables');

        $this->assertSame(ProjectSectionBlockType::Subtitle, $block->getType());
        $this->assertSame('Les tables', $block->getSubtitle());
    }

    public function testSectionNumbersItsBlocksAndCanBeCleared(): void
    {
        $section = new ProjectSection();
        $first = new ProjectSectionBlock(ProjectSectionBlockType::Paragraph);
        $second = new ProjectSectionBlock(ProjectSectionBlockType::Tags);

        $section->addBlock($first)->addBlock($second);

        $this->assertSame(0, $first->getPosition());
        $this->assertSame(1, $second->getPosition());
        $this->assertSame($section, $second->getSection());

        $section->clearBlocks();

        $this->assertCount(0, $section->getBlocks());
    }
}
