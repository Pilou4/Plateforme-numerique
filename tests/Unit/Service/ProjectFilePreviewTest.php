<?php

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Enum\ProjectFileType;
use App\Service\ProjectFileManager;
use App\Service\ProjectFilePreview;
use App\Service\ProjectStorage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ProjectFilePreviewTest extends TestCase
{
    private Filesystem $filesystem;
    private string $directory;
    private ProjectFilePreview $preview;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->directory = sys_get_temp_dir().'/plateforme-tests-'.bin2hex(random_bytes(4));

        $storage = new ProjectStorage($this->directory, $this->filesystem);
        $this->preview = new ProjectFilePreview(new ProjectFileManager($this->createStub(EntityManagerInterface::class), $storage));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->directory);
    }

    /* ------------------------------------------------------------------
       Type d'aperçu
       ------------------------------------------------------------------ */

    #[DataProvider('previewKindProvider')]
    public function testGetPreviewKind(ProjectFileType $type, string $mimeType, ?string $expected): void
    {
        $file = (new ProjectFile())->setType($type)->setMimeType($mimeType);

        $this->assertSame($expected, $this->preview->getPreviewKind($file));
    }

    public static function previewKindProvider(): iterable
    {
        yield 'png' => [ProjectFileType::Image, 'image/png', ProjectFilePreview::PREVIEW_IMAGE];
        yield 'svg' => [ProjectFileType::Image, 'image/svg+xml', ProjectFilePreview::PREVIEW_IMAGE];
        yield 'pdf' => [ProjectFileType::Document, 'application/pdf', ProjectFilePreview::PREVIEW_PDF];
        yield 'texte' => [ProjectFileType::Document, 'text/plain', ProjectFilePreview::PREVIEW_TEXT];
        yield 'markdown' => [ProjectFileType::Document, 'text/markdown', ProjectFilePreview::PREVIEW_TEXT];
        yield 'word : pas d\'aperçu' => [ProjectFileType::Document, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null];
    }

    /* ------------------------------------------------------------------
       Réponse envoyée au navigateur
       ------------------------------------------------------------------ */

    public function testPdfIsDisplayedInTheBrowser(): void
    {
        $response = $this->preview->createResponse($this->createFile('rapport.pdf', 'application/pdf'), false);

        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        // La visionneuse PDF ne fonctionne pas dans un « bac à sable »
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function testDownloadIsAlwaysAnAttachment(): void
    {
        $response = $this->preview->createResponse($this->createFile('rapport.pdf', 'application/pdf'), true);

        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('rapport.pdf', $response->headers->get('Content-Disposition'));
    }

    public function testFileWithoutPreviewIsAlwaysDownloaded(): void
    {
        $response = $this->preview->createResponse(
            $this->createFile('devis.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            false,
        );

        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
    }

    /**
     * Sécurité : un texte (même un .md qui contiendrait du HTML) est affiché en texte brut, sans rien exécuter.
     */
    public function testTextIsSentAsPlainTextInASandbox(): void
    {
        $response = $this->preview->createResponse($this->createFile('notes.md', 'text/markdown'), false);

        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
    }

    /**
     * Sécurité : un SVG peut contenir du JavaScript, il est ouvert dans un « bac à sable ».
     */
    public function testSvgIsSentInASandbox(): void
    {
        $response = $this->preview->createResponse($this->createFile('logo.svg', 'image/svg+xml', ProjectFileType::Image), false);

        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
    }

    public function testBrowserMustNotGuessTheType(): void
    {
        $response = $this->preview->createResponse($this->createFile('rapport.pdf', 'application/pdf'), false);

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /**
     * Fichier du projet 42, écrit pour de vrai dans le dossier temporaire
     * (la réponse a besoin d'un fichier qui existe).
     */
    private function createFile(string $originalName, string $mimeType, ProjectFileType $type = ProjectFileType::Document): ProjectFile
    {
        $project = new Project();
        (new \ReflectionProperty(Project::class, 'id'))->setValue($project, 42);

        $file = (new ProjectFile())
            ->setType($type)
            ->setTitle($originalName)
            ->setOriginalName($originalName)
            ->setFileName('fichier-test.'.pathinfo($originalName, \PATHINFO_EXTENSION))
            ->setMimeType($mimeType);
        $project->addFile($file);

        $this->filesystem->dumpFile($this->directory.'/42/'.$type->folder().'/'.$file->getFileName(), 'contenu');

        return $file;
    }
}
