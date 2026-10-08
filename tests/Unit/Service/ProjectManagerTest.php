<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectPayload;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Service\ProjectManager;
use App\Service\ProjectStorage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Le stockage est le vrai, dans un dossier temporaire supprimé à la fin de chaque test.
 */
final class ProjectManagerTest extends TestCase
{
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private Filesystem $filesystem;
    private string $directory;
    private ProjectStorage $storage;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->directory = sys_get_temp_dir().'/plateforme-tests-'.bin2hex(random_bytes(4));
        $this->storage = new ProjectStorage($this->directory, $this->filesystem);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->directory);
    }

    /* ------------------------------------------------------------------
       Création
       ------------------------------------------------------------------ */

    public function testCreateWithoutLogo(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Project::class));
        $entityManager->expects($this->once())->method('flush');

        $project = $this->createManager($entityManager)->create(new ProjectPayload('  Site vitrine Dupont  ', '  '), null);

        $this->assertSame('Site vitrine Dupont', $project->getName());
        $this->assertSame('site-vitrine-dupont', $project->getSlug());
        $this->assertNull($project->getSummary());
        $this->assertNull($project->getLogo());
    }

    public function testCreateWithLogo(): void
    {
        // La base donne l'id 7 au projet au moment du persist (simulé ici)
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->willReturnCallback(
            static function (Project $project): void {
                (new \ReflectionProperty(Project::class, 'id'))->setValue($project, 7);
            },
        );
        // Une fois pour avoir l'id, une fois pour le logo
        $entityManager->expects($this->exactly(2))->method('flush');

        $project = $this->createManager($entityManager)->create(new ProjectPayload('Plateforme'), $this->createUploadedFile());

        $this->assertNotNull($project->getLogo());
        $this->assertFileExists($this->directory.'/7/'.$project->getLogo());
    }

    public function testCreateWithAnExistingNameIsRefused(): void
    {
        $manager = $this->createManager(takenNames: ['Plateforme']);

        $this->expectException(\DomainException::class);

        $manager->create(new ProjectPayload('Plateforme'), null);
    }

    public function testSlugAlreadyTakenGetsANumber(): void
    {
        // Un autre projet a déjà le slug « plateforme » (nom différent, ex. « Plateforme ! »)
        $project = $this->createManager(takenSlugs: ['plateforme'])->create(new ProjectPayload('Plateforme'), null);

        $this->assertSame('plateforme-2', $project->getSlug());
    }

    /* ------------------------------------------------------------------
       Modification
       ------------------------------------------------------------------ */

    public function testUpdateChangesTheSlugWithTheName(): void
    {
        $project = $this->createProject();

        $this->createManager()->update($project, new ProjectPayload('Nouveau nom', 'Résumé'), null, false);

        $this->assertSame('nouveau-nom', $project->getSlug());
        $this->assertSame('Résumé', $project->getSummary());
    }

    public function testUpdateKeepingItsOwnNameIsAllowed(): void
    {
        $project = $this->createProject();

        // Le repository trouve… le projet lui-même : ce n'est pas un doublon
        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('findOneBy')->willReturn($project);

        (new ProjectManager($this->createStub(EntityManagerInterface::class), $repository, new AsciiSlugger(), $this->storage))
            ->update($project, new ProjectPayload('Plateforme'), null, false);

        $this->assertSame('plateforme', $project->getSlug());
    }

    public function testUpdateWithANewLogoDeletesTheOldOne(): void
    {
        $project = $this->createProject();
        $oldLogo = $this->storage->store($project, $this->createUploadedFile(), 'logo');
        $project->setLogo($oldLogo);

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), $this->createUploadedFile(), false);

        $this->assertNotSame($oldLogo, $project->getLogo());
        $this->assertFalse($this->storage->exists($project, $oldLogo));
        $this->assertTrue($this->storage->exists($project, $project->getLogo()));
    }

    public function testUpdateRemovingTheLogo(): void
    {
        $project = $this->createProject();
        $oldLogo = $this->storage->store($project, $this->createUploadedFile(), 'logo');
        $project->setLogo($oldLogo);

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), null, true);

        $this->assertNull($project->getLogo());
        $this->assertFalse($this->storage->exists($project, $oldLogo));
    }

    public function testUpdateWithoutLogoChangeKeepsTheLogo(): void
    {
        $project = $this->createProject()->setLogo('logo.png');

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), null, false);

        $this->assertSame('logo.png', $project->getLogo());
    }

    /**
     * @param list<string> $takenNames noms déjà utilisés par d'autres projets
     * @param list<string> $takenSlugs slugs déjà utilisés par d'autres projets
     */
    private function createManager(?EntityManagerInterface $entityManager = null, array $takenNames = [], array $takenSlugs = []): ProjectManager
    {
        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            static function (array $criteria) use ($takenNames, $takenSlugs): ?Project {
                $taken = isset($criteria['name']) ? $takenNames : $takenSlugs;
                $value = $criteria['name'] ?? $criteria['slug'];

                return \in_array($value, $taken, true) ? new Project() : null;
            },
        );

        return new ProjectManager(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $repository,
            new AsciiSlugger(),
            $this->storage,
        );
    }

    private function createProject(): Project
    {
        $project = (new Project())->setName('Plateforme')->setSlug('plateforme');
        (new \ReflectionProperty(Project::class, 'id'))->setValue($project, 42);

        return $project;
    }

    private function createUploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, base64_decode(self::PNG));

        return new UploadedFile($path, 'logo.png', 'image/png', null, true);
    }
}
