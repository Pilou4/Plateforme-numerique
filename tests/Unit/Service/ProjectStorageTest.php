<?php

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Service\ProjectStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Le stockage écrit de vrais fichiers : chaque test travaille dans un dossier temporaire,
 * supprimé à la fin (tearDown), pour ne jamais toucher à var/storage.
 */
final class ProjectStorageTest extends TestCase
{
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

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

    public function testPathOfTheLogoIsInTheProjectFolder(): void
    {
        $this->assertSame($this->directory.'/42/logo.png', $this->storage->getPath($this->project, 'logo.png'));
    }

    public function testPathWithASubFolder(): void
    {
        $this->assertSame($this->directory.'/42/images/photo.png', $this->storage->getPath($this->project, 'photo.png', 'images'));
    }

    /**
     * Sécurité : un nom de fichier ou de dossier ne peut pas sortir du dossier du projet.
     */
    public function testPathCannotGoUpTheTree(): void
    {
        $this->assertSame($this->directory.'/42/passwd', $this->storage->getPath($this->project, '../../etc/passwd'));
        $this->assertSame($this->directory.'/42/secret/fichier.txt', $this->storage->getPath($this->project, 'fichier.txt', '../../secret'));
    }

    public function testStoreMovesTheFileWithANewName(): void
    {
        $fileName = $this->storage->store($this->project, $this->createUploadedFile(), 'logo');

        $this->assertMatchesRegularExpression('/^logo-[0-9a-f]{16}\.png$/', $fileName);
        $this->assertFileExists($this->directory.'/42/'.$fileName);
    }

    public function testStoreInASubFolder(): void
    {
        $fileName = $this->storage->store($this->project, $this->createUploadedFile(), 'fichier', 'images');

        $this->assertFileExists($this->directory.'/42/images/'.$fileName);
    }

    public function testTwoFilesNeverGetTheSameName(): void
    {
        $first = $this->storage->store($this->project, $this->createUploadedFile(), 'logo');
        $second = $this->storage->store($this->project, $this->createUploadedFile(), 'logo');

        $this->assertNotSame($first, $second);
    }

    public function testExists(): void
    {
        $fileName = $this->storage->store($this->project, $this->createUploadedFile(), 'logo');

        $this->assertTrue($this->storage->exists($this->project, $fileName));
        $this->assertFalse($this->storage->exists($this->project, 'absent.png'));
    }

    public function testDeleteRemovesTheFile(): void
    {
        $fileName = $this->storage->store($this->project, $this->createUploadedFile(), 'logo');

        $this->storage->delete($this->project, $fileName);

        $this->assertFalse($this->storage->exists($this->project, $fileName));
    }

    public function testDeleteAMissingFileDoesNothing(): void
    {
        $this->storage->delete($this->project, 'absent.png');

        $this->assertFalse($this->storage->exists($this->project, 'absent.png'));
    }

    /**
     * Faux fichier envoyé par le navigateur : une vraie image PNG de 1 pixel.
     * Le dernier argument (true) indique à Symfony que c'est un test (pas un vrai envoi HTTP).
     */
    private function createUploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, base64_decode(self::PNG));

        return new UploadedFile($path, 'logo.png', 'image/png', null, true);
    }
}
