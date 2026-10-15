<?php

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Entity\ProjectSection;
use App\Entity\ProjectSectionBlock;
use App\Enum\ProjectFileType;
use App\Enum\ProjectSectionBlockType;
use App\Repository\ProjectFileRepository;
use App\Service\ProjectSectionBlockBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProjectSectionBlockBuilderTest extends TestCase
{
    private Project $project;
    private ProjectSection $section;

    /** @var array<int, ProjectFile> fichiers en base, par id */
    private array $files = [];

    protected function setUp(): void
    {
        $this->project = (new Project())->setName('Plateforme')->setSlug('plateforme');
        $this->section = (new ProjectSection())->setTitle('Base de données')->setProject($this->project);

        $this->files = [
            1 => $this->createFile(1, 'Schéma', ProjectFileType::Image, $this->project),
            2 => $this->createFile(2, 'Cahier des charges', ProjectFileType::Document, $this->project),
            3 => $this->createFile(3, 'Image d\'un autre projet', ProjectFileType::Image, (new Project())->setName('Autre')->setSlug('autre')),
        ];
    }

    /* ------------------------------------------------------------------
       Paragraphe, sous-titre, liste
       ------------------------------------------------------------------ */

    public function testEachNonEmptyLineBecomesAParagraph(): void
    {
        $block = $this->buildOne(['type' => 'paragraphe', 'text' => "  Ligne 1  \n\n\r\nLigne 2\n   "]);

        $this->assertSame(ProjectSectionBlockType::Paragraph, $block->getType());
        $this->assertSame(['Ligne 1', 'Ligne 2'], array_map(static fn ($paragraph): string => $paragraph->getText(), $block->getParagraphs()->toArray()));
    }

    public function testSubtitleIsTrimmed(): void
    {
        $block = $this->buildOne(['type' => 'sous_titre', 'text' => '  Les tables  ']);

        $this->assertSame('Les tables', $block->getSubtitle());
    }

    public function testListIgnoresEmptyItems(): void
    {
        $block = $this->buildOne(['type' => 'liste', 'ordered' => true, 'items' => ['Docker', '  ', 'Git ']]);

        $this->assertTrue($block->isOrdered());
        $this->assertSame(['Docker', 'Git'], array_map(static fn ($item): string => $item->getText(), $block->getListItems()->toArray()));
    }

    public function testListIsUnorderedUnlessOrderedIsTrue(): void
    {
        $block = $this->buildOne(['type' => 'liste', 'ordered' => 'oui', 'items' => ['a']]);

        $this->assertFalse($block->isOrdered());
    }

    /* ------------------------------------------------------------------
       Tableau
       ------------------------------------------------------------------ */

    public function testTableKeepsColumnsAndFilledRows(): void
    {
        $block = $this->buildOne([
            'type' => 'tableau',
            'columns' => ['Table', 'Rôle'],
            'rows' => [['project', 'Un projet'], ['', '  '], ['project_step', '']],
        ]);

        $this->assertSame(['Table', 'Rôle'], array_map(static fn ($column): string => $column->getLabel(), $block->getTableColumns()->toArray()));
        $this->assertCount(2, $block->getTableRows());

        $lastRow = $block->getTableRows()[1]->getOrderedCells();
        $this->assertSame('project_step', $lastRow[0]->getText());
        $this->assertSame('', $lastRow[1]->getText());
    }

    public function testTableWithoutColumnIsRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Tableau) : le tableau doit avoir au moins une colonne.'));

        $this->build([['type' => 'tableau', 'columns' => [], 'rows' => [['a']]]]);
    }

    public function testTableRowWithMissingCellIsRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Tableau) : chaque ligne doit avoir une case par colonne.'));

        $this->build([['type' => 'tableau', 'columns' => ['A', 'B'], 'rows' => [['seule']]]]);
    }

    public function testTableWithOnlyEmptyRowsIsRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Tableau) : remplis au moins une ligne du tableau, ou supprime le bloc.'));

        $this->build([['type' => 'tableau', 'columns' => ['A'], 'rows' => [[''], [' ']]]]);
    }

    public function testTableWithTooManyColumnsIsRefused(): void
    {
        $columns = array_fill(0, ProjectSectionBlockBuilder::MAX_TABLE_COLUMNS + 1, 'Col');

        $this->expectException(\DomainException::class);

        $this->build([['type' => 'tableau', 'columns' => $columns, 'rows' => [$columns]]]);
    }

    /* ------------------------------------------------------------------
       Tags et définitions
       ------------------------------------------------------------------ */

    public function testDuplicateTagsAreKeptOnce(): void
    {
        $block = $this->buildOne(['type' => 'tags', 'tags' => ['MySQL', 'mysql', ' Doctrine ', '', 'MYSQL']]);

        $this->assertSame(['MySQL', 'Doctrine'], array_map(static fn ($tag): string => $tag->getLabel(), $block->getTags()->toArray()));
    }

    public function testTagTooLongIsRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Tags) : un texte dépasse 100 caractères.'));

        $this->build([['type' => 'tags', 'tags' => [str_repeat('a', 101)]]]);
    }

    public function testEmptyDefinitionPairsAreIgnored(): void
    {
        $block = $this->buildOne(['type' => 'definitions', 'items' => [
            ['term' => 'Entité', 'definition' => 'Une classe PHP.'],
            ['term' => ' ', 'definition' => ''],
        ]]);

        $this->assertCount(1, $block->getDefinitions());
        $this->assertSame('Entité', $block->getDefinitions()[0]->getTerm());
    }

    public function testHalfFilledDefinitionIsRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Définitions) : chaque terme doit avoir sa définition (et inversement).'));

        $this->build([['type' => 'definitions', 'items' => [['term' => 'Entité', 'definition' => '']]]]);
    }

    /* ------------------------------------------------------------------
       Images et documents
       ------------------------------------------------------------------ */

    public function testImagesBlockKeepsFilesOnceWithTheirCaption(): void
    {
        $block = $this->buildOne(['type' => 'images', 'files' => [
            ['id' => 1, 'caption' => ' Schéma des tables '],
            ['id' => 1, 'caption' => 'Doublon'],
        ]]);

        $this->assertCount(1, $block->getFiles());
        $this->assertSame($this->files[1], $block->getFiles()[0]->getFile());
        $this->assertSame('Schéma des tables', $block->getFiles()[0]->getCaption());
    }

    public function testEmptyCaptionIsNull(): void
    {
        $block = $this->buildOne(['type' => 'documents', 'files' => [['id' => 2, 'caption' => '  ']]]);

        $this->assertNull($block->getFiles()[0]->getCaption());
    }

    public function testDocumentsBlockAcceptsImagesToo(): void
    {
        $block = $this->buildOne(['type' => 'documents', 'files' => [['id' => 1], ['id' => 2]]]);

        $this->assertCount(2, $block->getFiles());
    }

    public function testImagesBlockRefusesADocument(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Images) : « Cahier des charges » n\'est pas une image (utilise un bloc Documents).'));

        $this->build([['type' => 'images', 'files' => [['id' => 2]]]]);
    }

    #[DataProvider('unknownFileProvider')]
    public function testFileOutsideTheProjectIsRefused(mixed $id): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Documents) : un des fichiers n\'existe plus dans ce projet.'));

        $this->build([['type' => 'documents', 'files' => [['id' => $id]]]]);
    }

    public static function unknownFileProvider(): iterable
    {
        yield 'autre projet' => [3];
        yield 'supprimé' => [99];
        yield 'id absent' => [null];
        yield 'id texte' => ['abc'];
    }

    /* ------------------------------------------------------------------
       Règles communes
       ------------------------------------------------------------------ */

    #[DataProvider('emptyBlockProvider')]
    public function testEmptyBlockIsRefusedWithItsNumber(array $data, string $label): void
    {
        $this->expectExceptionObject(new \DomainException(\sprintf('Bloc 2 (%s) : le bloc est vide, remplis-le ou supprime-le.', $label)));

        $this->build([['type' => 'sous_titre', 'text' => 'Correct'], $data]);
    }

    public static function emptyBlockProvider(): iterable
    {
        yield 'paragraphe' => [['type' => 'paragraphe', 'text' => " \n "], 'Paragraphe'];
        yield 'sous-titre' => [['type' => 'sous_titre', 'text' => ''], 'Sous-titre'];
        yield 'liste' => [['type' => 'liste', 'items' => ['', ' ']], 'Liste'];
        yield 'tags' => [['type' => 'tags', 'tags' => []], 'Tags'];
        yield 'définitions' => [['type' => 'definitions', 'items' => []], 'Définitions'];
        yield 'images' => [['type' => 'images', 'files' => []], 'Images'];
        yield 'documents' => [['type' => 'documents'], 'Documents'];
    }

    public function testUnknownTypeIsRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 : ce type de bloc n\'existe pas.'));

        $this->build([['type' => 'video']]);
    }

    public function testBlockThatIsNotAnArrayIsRefused(): void
    {
        $this->expectException(\DomainException::class);

        $this->build(['texte']);
    }

    public function testTextTooLongIsRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Sous-titre) : un texte dépasse 255 caractères.'));

        $this->build([['type' => 'sous_titre', 'text' => str_repeat('a', 256)]]);
    }

    public function testTooManyItemsAreRefused(): void
    {
        $this->expectExceptionObject(new \DomainException('Bloc 1 (Liste) : 200 éléments au maximum.'));

        $this->build([['type' => 'liste', 'items' => array_fill(0, ProjectSectionBlockBuilder::MAX_ITEMS + 1, 'a')]]);
    }

    public function testBlocksReplaceTheOldOnesInOrder(): void
    {
        $this->build([['type' => 'paragraphe', 'text' => 'Ancien']]);
        $this->build([
            ['type' => 'sous_titre', 'text' => 'Nouveau'],
            ['type' => 'tags', 'tags' => ['a']],
        ]);

        $blocks = array_values($this->section->getBlocks()->toArray());

        $this->assertCount(2, $blocks);
        $this->assertSame(ProjectSectionBlockType::Subtitle, $blocks[0]->getType());
        $this->assertSame(1, $blocks[1]->getPosition());
    }

    public function testRefusedBlockKeepsTheOldBlocks(): void
    {
        $this->build([['type' => 'paragraphe', 'text' => 'Ancien']]);

        try {
            $this->build([
                ['type' => 'sous_titre', 'text' => 'Nouveau'],
                ['type' => 'liste', 'items' => []],
            ]);
            $this->fail('Le deuxième bloc aurait dû être refusé.');
        } catch (\DomainException) {
        }

        $this->assertCount(1, $this->section->getBlocks());
        $this->assertSame(ProjectSectionBlockType::Paragraph, $this->section->getBlocks()->first()->getType());
    }

    public function testNoBlockEmptiesTheSection(): void
    {
        $this->build([['type' => 'paragraphe', 'text' => 'Ancien']]);
        $this->build([]);

        $this->assertCount(0, $this->section->getBlocks());
    }

    /**
     * @param list<mixed> $blocks
     */
    private function build(array $blocks): void
    {
        $repository = $this->createStub(ProjectFileRepository::class);
        $repository->method('find')->willReturnCallback(fn (mixed $id): ?ProjectFile => $this->files[$id] ?? null);

        (new ProjectSectionBlockBuilder($repository))->replaceBlocks($this->section, $blocks);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildOne(array $data): ProjectSectionBlock
    {
        $this->build([$data]);

        return $this->section->getBlocks()->first();
    }

    private function createFile(int $id, string $title, ProjectFileType $type, Project $project): ProjectFile
    {
        $file = (new ProjectFile())->setTitle($title)->setType($type)->setProject($project);
        (new \ReflectionProperty(ProjectFile::class, 'id'))->setValue($file, $id);

        return $file;
    }
}
