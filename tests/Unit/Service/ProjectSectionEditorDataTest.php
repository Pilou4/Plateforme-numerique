<?php

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Entity\ProjectSection;
use App\Entity\ProjectSectionBlock;
use App\Enum\ProjectFileType;
use App\Enum\ProjectSectionBlockType;
use App\Service\ProjectSectionEditorData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProjectSectionEditorDataTest extends TestCase
{
    public function testSectionDataHasTheSameFormatAsThePayload(): void
    {
        $project = (new Project())->setName('Plateforme')->setSlug('plateforme');
        $image = (new ProjectFile())->setTitle('Schéma')->setType(ProjectFileType::Image)->setOriginalName('schema.png')->setSize(2048)->setProject($project);
        (new \ReflectionProperty(ProjectFile::class, 'id'))->setValue($image, 7);

        $table = new ProjectSectionBlock(ProjectSectionBlockType::Table);
        $table->addTableColumn('Table');
        $table->addTableColumn('Rôle');
        $table->addTableRow(['project', 'Un projet']);

        $section = (new ProjectSection())->setTitle('Base de données')
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Paragraph))->addParagraph('Ligne 1')->addParagraph('Ligne 2'))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Subtitle))->setSubtitle('Les tables'))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::List))->setOrdered(true)->addListItem('Docker'))
            ->addBlock($table)
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Tags))->addTag('MySQL'))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Definitions))->addDefinition('Entité', 'Une classe.'))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Images))->addFile($image, 'Le schéma'));

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())->method('generate')
            ->with('app_project_file', ['slug' => 'plateforme', 'id' => 7])
            ->willReturn('/app/projet/plateforme/fichier/7');

        $data = (new ProjectSectionEditorData($urlGenerator))->forSection($section);

        $this->assertSame('Base de données', $data['title']);
        $this->assertSame([
            ['type' => 'paragraphe', 'text' => "Ligne 1\nLigne 2"],
            ['type' => 'sous_titre', 'text' => 'Les tables'],
            ['type' => 'liste', 'ordered' => true, 'items' => ['Docker']],
            ['type' => 'tableau', 'columns' => ['Table', 'Rôle'], 'rows' => [['project', 'Un projet']]],
            ['type' => 'tags', 'tags' => ['MySQL']],
            ['type' => 'definitions', 'items' => [['term' => 'Entité', 'definition' => 'Une classe.']]],
            ['type' => 'images', 'files' => [[
                'caption' => 'Le schéma',
                'id' => 7,
                'type' => 'image',
                'title' => 'Schéma',
                'extension' => 'png',
                'size' => 2048,
                'viewUrl' => '/app/projet/plateforme/fichier/7',
            ]]],
        ], $data['blocks']);
    }
}
