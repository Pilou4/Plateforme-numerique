<?php

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Entity\ProjectSection;
use App\Entity\ProjectSectionBlock;
use App\Enum\ProjectFileType;
use App\Enum\ProjectSectionBlockType;
use App\Enum\ProjectSectionTableStyle;
use App\Enum\ProjectSectionTagColor;
use App\Service\ProjectFileManager;
use App\Service\ProjectFilePreview;
use App\Service\ProjectSectionEditorData;
use App\Service\ProjectStorage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProjectSectionEditorDataTest extends TestCase
{
    public function testSectionDataHasTheSameFormatAsThePayload(): void
    {
        $project = (new Project())->setName('Plateforme')->setSlug('plateforme');
        $image = (new ProjectFile())->setTitle('Schéma')->setType(ProjectFileType::Image)->setOriginalName('schema.png')->setSize(2048)->setProject($project);
        (new \ReflectionProperty(ProjectFile::class, 'id'))->setValue($image, 7);

        $table = (new ProjectSectionBlock(ProjectSectionBlockType::Table))->setTableStyle(ProjectSectionTableStyle::NavyHead);
        $table->addTableColumn('Table');
        $table->addTableColumn('Rôle', false);
        $table->addTableRow(['project', 'Un projet']);

        $section = (new ProjectSection())->setTitle('Base de données')
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Paragraph))->addParagraph('Ligne 1')->addParagraph('Ligne 2'))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Subtitle))->setSubtitle('Les tables'))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::List))->setOrdered(true)->addListItem('Docker'))
            ->addBlock($table)
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Tags))->addTag('MySQL')->addTag('Docker', ProjectSectionTagColor::Cyan))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Definitions))->addDefinition('Entité', 'Une classe.'))
            ->addBlock((new ProjectSectionBlock(ProjectSectionBlockType::Images))->addFile($image, 'Le schéma'));

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())->method('generate')
            ->with('app_project_file', ['slug' => 'plateforme', 'id' => 7])
            ->willReturn('/app/projet/plateforme/fichier/7');

        $data = (new ProjectSectionEditorData($urlGenerator, $this->createPreview()))->forSection($section);

        $this->assertSame('Base de données', $data['title']);
        $this->assertSame([
            ['type' => 'paragraphe', 'text' => "Ligne 1\nLigne 2"],
            ['type' => 'sous_titre', 'text' => 'Les tables'],
            ['type' => 'liste', 'ordered' => true, 'items' => ['Docker']],
            [
                'type' => 'tableau',
                'style' => 'entete_marine',
                'columns' => [['label' => 'Table', 'sortable' => true], ['label' => 'Rôle', 'sortable' => false]],
                'rows' => [['project', 'Un projet']],
            ],
            ['type' => 'tags', 'tags' => [['label' => 'MySQL', 'color' => 'bleu'], ['label' => 'Docker', 'color' => 'cyan']]],
            ['type' => 'definitions', 'items' => [['term' => 'Entité', 'definition' => 'Une classe.']]],
            ['type' => 'images', 'files' => [[
                'caption' => 'Le schéma',
                'id' => 7,
                'type' => 'image',
                'title' => 'Schéma',
                'extension' => 'png',
                'size' => 2048,
                'preview' => 'image',
                'viewUrl' => '/app/projet/plateforme/fichier/7',
            ]]],
        ], $data['blocks']);
    }

    public function testTableSavedBeforeTheStyleChoiceGetsTheDefaultStyle(): void
    {
        $table = new ProjectSectionBlock(ProjectSectionBlockType::Table);
        $table->addTableColumn('Réglage');
        $table->addTableRow(['Port']);

        $data = (new ProjectSectionEditorData($this->createStub(UrlGeneratorInterface::class), $this->createPreview()))
            ->forSection((new ProjectSection())->setTitle('Réglages')->addBlock($table));

        $this->assertSame('liste_finder', $data['blocks'][0]['style']);
    }

    private function createPreview(): ProjectFilePreview
    {
        $storage = new ProjectStorage(sys_get_temp_dir().'/plateforme-tests-inutilise', new Filesystem());

        return new ProjectFilePreview(new ProjectFileManager($this->createStub(EntityManagerInterface::class), $storage));
    }
}
