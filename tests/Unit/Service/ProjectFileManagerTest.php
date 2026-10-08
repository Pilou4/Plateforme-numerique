<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectFilePayload;
use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Enum\ProjectFileType;
use App\Service\ProjectFileManager;
use App\Service\ProjectStorage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Le stockage est le vrai, dans un dossier temporaire supprimé à la fin de chaque test.
 */
final class ProjectFileManagerTest extends TestCase
{
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    private const string TEXT = "Bonjour, ceci est un fichier texte.\n";

    private Filesystem $filesystem;
    private string $directory;
    private ProjectStorage $storage;
    private Project $project;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->directory = sys_get_temp_dir().'/plateforme-tests-'.bin2hex(random_bytes(4));
        $this->storage = new ProjectStorage($this->directory, $this->filesystem);

        $this->project = new Project();
        (new \ReflectionProperty(Project::class, 'id'))->setValue($this->project, 42);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->directory);
    }

    /* ------------------------------------------------------------------
       Ajout
       ------------------------------------------------------------------ */

    public function testUploadAnImage(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(ProjectFile::class));
        $entityManager->expects($this->once())->method('flush');

        $file = (new ProjectFileManager($entityManager, $this->storage))
            ->upload($this->project, $this->createUploadedFile(base64_decode(self::PNG), 'maquette-accueil.png'), new ProjectFilePayload());

        $this->assertSame(ProjectFileType::Image, $file->getType());
        $this->assertSame('image/png', $file->getMimeType());
        $this->assertSame('maquette-accueil.png', $file->getOriginalName());
        $this->assertSame(\strlen(base64_decode(self::PNG)), $file->getSize());
        $this->assertSame($this->project, $file->getProject());
        $this->assertTrue($this->project->getFiles()->contains($file));
        $this->assertFileExists($this->directory.'/42/images/'.$file->getFileName());
    }

    public function testUploadADocument(): void
    {
        $file = $this->createManager()->upload($this->project, $this->createUploadedFile(self::TEXT, 'notes.txt'), new ProjectFilePayload());

        $this->assertSame(ProjectFileType::Document, $file->getType());
        $this->assertSame('text/plain', $file->getMimeType());
        $this->assertFileExists($this->directory.'/42/documents/'.$file->getFileName());
    }

    public function testUploadWithoutTitleUsesTheFileName(): void
    {
        $file = $this->createManager()->upload($this->project, $this->createUploadedFile(self::TEXT, 'cahier-des-charges.txt'), new ProjectFilePayload('   '));

        $this->assertSame('cahier-des-charges', $file->getTitle());
    }

    public function testUploadWithTitleAndDescription(): void
    {
        $file = $this->createManager()->upload(
            $this->project,
            $this->createUploadedFile(self::TEXT, 'notes.txt'),
            new ProjectFilePayload('  Notes de réunion  ', '  Réunion du lundi  '),
        );

        $this->assertSame('Notes de réunion', $file->getTitle());
        $this->assertSame('Réunion du lundi', $file->getDescription());
    }

    /**
     * Sécurité : le nom d'origine ne garde ni chemin ni caractère de contrôle.
     */
    public function testUploadCleansTheOriginalName(): void
    {
        $file = $this->createManager()->upload($this->project, $this->createUploadedFile(self::TEXT, "rap\x07port.txt"), new ProjectFilePayload());

        $this->assertSame('rapport.txt', $file->getOriginalName());
    }

    public function testFileNameOnDiskIsNeverTheOriginalName(): void
    {
        $file = $this->createManager()->upload($this->project, $this->createUploadedFile(self::TEXT, 'notes.txt'), new ProjectFilePayload());

        $this->assertMatchesRegularExpression('/^fichier-[0-9a-f]{16}\.txt$/', $file->getFileName());
    }

    /* ------------------------------------------------------------------
       Modification
       ------------------------------------------------------------------ */

    public function testUpdateChangesTitleAndDescription(): void
    {
        $file = (new ProjectFile())->setTitle('Ancien')->setDescription('Ancienne');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        (new ProjectFileManager($entityManager, $this->storage))->update($file, new ProjectFilePayload(' Nouveau ', ''));

        $this->assertSame('Nouveau', $file->getTitle());
        $this->assertNull($file->getDescription());
    }

    public function testUpdateWithoutTitleIsRefused(): void
    {
        $this->expectException(\DomainException::class);

        $this->createManager()->update((new ProjectFile())->setTitle('Titre'), new ProjectFilePayload('   '));
    }

    /* ------------------------------------------------------------------
       Suppression et chemin
       ------------------------------------------------------------------ */

    public function testDeleteRemovesTheFileFromDiskAndProject(): void
    {
        $file = $this->createManager()->upload($this->project, $this->createUploadedFile(self::TEXT, 'notes.txt'), new ProjectFilePayload());
        $path = $this->directory.'/42/documents/'.$file->getFileName();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($file);
        $entityManager->expects($this->once())->method('flush');

        (new ProjectFileManager($entityManager, $this->storage))->delete($file);

        $this->assertFileDoesNotExist($path);
        $this->assertCount(0, $this->project->getFiles());
    }

    public function testGetPathAndExists(): void
    {
        $manager = $this->createManager();
        $file = $manager->upload($this->project, $this->createUploadedFile(base64_decode(self::PNG), 'photo.png'), new ProjectFilePayload());

        $this->assertSame($this->directory.'/42/images/'.$file->getFileName(), $manager->getPath($file));
        $this->assertTrue($manager->exists($file));
    }

    private function createManager(): ProjectFileManager
    {
        return new ProjectFileManager($this->createStub(EntityManagerInterface::class), $this->storage);
    }

    private function createUploadedFile(string $content, string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return new UploadedFile($path, $originalName, null, null, true);
    }
}
