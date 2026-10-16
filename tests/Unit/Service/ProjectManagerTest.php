<?php

namespace App\Tests\Unit\Service;

use App\Dto\ProjectPayload;
use App\Entity\Project;
use App\Entity\ProjectCategory;
use App\Entity\ProjectState;
use App\Enum\ProjectImage;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStateRepository;
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

    /** @var array<string, ProjectState> */
    private array $states = [];
    private ProjectCategory $category;

    protected function setUp(): void
    {
        foreach (ProjectState::CODES as $position => $code) {
            $this->states[$code] = new ProjectState($code, $code, $code, $position);
        }

        $this->category = new ProjectCategory('Jeu', 0);

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

        $project = $this->createManager($entityManager)->create(new ProjectPayload('  Site vitrine Dupont  ', '  '));

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

        $project = $this->createManager($entityManager)->create(new ProjectPayload('Plateforme'), ['logo' => $this->createUploadedFile()]);

        $this->assertNotNull($project->getLogo());
        $this->assertFileExists($this->directory.'/7/'.$project->getLogo());
    }

    public function testCreateWithAnExistingNameIsRefused(): void
    {
        $manager = $this->createManager(takenNames: ['Plateforme']);

        $this->expectException(\DomainException::class);

        $manager->create(new ProjectPayload('Plateforme'));
    }

    public function testSlugAlreadyTakenGetsANumber(): void
    {
        // Un autre projet a déjà le slug « plateforme » (nom différent, ex. « Plateforme ! »)
        $project = $this->createManager(takenSlugs: ['plateforme'])->create(new ProjectPayload('Plateforme'));

        $this->assertSame('plateforme-2', $project->getSlug());
    }

    /* ------------------------------------------------------------------
       Modification
       ------------------------------------------------------------------ */

    public function testUpdateChangesTheSlugWithTheName(): void
    {
        $project = $this->createProject();

        $this->createManager()->update($project, new ProjectPayload('Nouveau nom', 'Résumé'));

        $this->assertSame('nouveau-nom', $project->getSlug());
        $this->assertSame('Résumé', $project->getSummary());
    }

    public function testUpdateKeepingItsOwnNameIsAllowed(): void
    {
        $project = $this->createProject();

        // Le repository trouve… le projet lui-même : ce n'est pas un doublon
        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('findOneBy')->willReturn($project);

        (new ProjectManager($this->createStub(EntityManagerInterface::class), $repository, new AsciiSlugger(), $this->storage, $this->createStateRepository(), $this->createCategoryRepository()))
            ->update($project, new ProjectPayload('Plateforme'));

        $this->assertSame('plateforme', $project->getSlug());
    }

    public function testUpdateWithANewLogoDeletesTheOldOne(): void
    {
        $project = $this->createProject();
        $oldLogo = $this->storage->store($project, $this->createUploadedFile(), 'logo');
        $project->setLogo($oldLogo);

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), ['logo' => $this->createUploadedFile()]);

        $this->assertNotSame($oldLogo, $project->getLogo());
        $this->assertFalse($this->storage->exists($project, $oldLogo));
        $this->assertTrue($this->storage->exists($project, $project->getLogo()));
    }

    public function testUpdateRemovingTheLogo(): void
    {
        $project = $this->createProject();
        $oldLogo = $this->storage->store($project, $this->createUploadedFile(), 'logo');
        $project->setLogo($oldLogo);

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), [], [ProjectImage::Logo]);

        $this->assertNull($project->getLogo());
        $this->assertFalse($this->storage->exists($project, $oldLogo));
    }

    public function testUpdateWithoutLogoChangeKeepsTheLogo(): void
    {
        $project = $this->createProject()->setLogo('logo.png');

        $this->createManager()->update($project, new ProjectPayload('Plateforme'));

        $this->assertSame('logo.png', $project->getLogo());
    }

    /* ------------------------------------------------------------------
       Liens et suppression d'une image depuis la page du projet
       ------------------------------------------------------------------ */

    public function testCreateWithLinks(): void
    {
        $project = $this->createManager()->create(new ProjectPayload('Plateforme', repositoryUrl: ' https://github.com/fred/plateforme ', websiteUrl: ''));

        $this->assertSame('https://github.com/fred/plateforme', $project->getRepositoryUrl());
        $this->assertNull($project->getWebsiteUrl());
    }

    public function testUpdateChangesOrRemovesTheLinks(): void
    {
        $project = $this->createProject()->setRepositoryUrl('https://github.com/fred/ancien')->setWebsiteUrl('https://ancien.fr');

        $this->createManager()->update($project, new ProjectPayload('Plateforme', repositoryUrl: 'https://github.com/fred/nouveau', websiteUrl: ''));

        $this->assertSame('https://github.com/fred/nouveau', $project->getRepositoryUrl());
        $this->assertNull($project->getWebsiteUrl());
    }

    /**
     * Les cartes de la page Projets n'envoient pas les liens : ils ne doivent pas être effacés.
     */
    public function testUpdateWithoutLinksKeepsThem(): void
    {
        $project = $this->createProject()->setRepositoryUrl('https://github.com/fred/plateforme')->setWebsiteUrl('https://plateforme.fr');

        $this->createManager()->update($project, new ProjectPayload('Plateforme', 'Nouvelle description'));

        $this->assertSame('https://github.com/fred/plateforme', $project->getRepositoryUrl());
        $this->assertSame('https://plateforme.fr', $project->getWebsiteUrl());
    }

    public function testRemoveImageDeletesOnlyThisImage(): void
    {
        $project = $this->createProject();
        $logoWide = $this->storage->store($project, $this->createUploadedFile(), 'logo-long');
        $avatar = $this->storage->store($project, $this->createUploadedFile(), 'avatar');
        $project->setLogoWide($logoWide)->setAvatar($avatar);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $this->createManager($entityManager)->removeImage($project, ProjectImage::LogoWide);

        $this->assertNull($project->getLogoWide());
        $this->assertFalse($this->storage->exists($project, $logoWide));
        $this->assertSame($avatar, $project->getAvatar());
        $this->assertTrue($this->storage->exists($project, $avatar));
    }

    /* ------------------------------------------------------------------
       État, catégorie et favori
       ------------------------------------------------------------------ */

    /**
     * Sans statut dans le formulaire, le projet prend le premier statut de la liste.
     */
    public function testNewProjectGetsTheFirstStateWithoutCategory(): void
    {
        $project = $this->createManager()->create(new ProjectPayload('Plateforme'));

        $this->assertSame($this->states[ProjectState::CODE_NOT_STARTED], $project->getState());
        $this->assertNull($project->getCategory());
        $this->assertFalse($project->isFavorite());
    }

    public function testCreateWithStateAndCategory(): void
    {
        $project = $this->createManager()->create(new ProjectPayload('Plateforme', null, ProjectState::CODE_IN_PROGRESS, '1'));

        $this->assertSame($this->states[ProjectState::CODE_IN_PROGRESS], $project->getState());
        $this->assertSame($this->category, $project->getCategory());
    }

    public function testUnknownCategoryIsRefused(): void
    {
        $this->expectException(\DomainException::class);

        $this->createManager()->create(new ProjectPayload('Plateforme', null, null, '99'));
    }

    public function testUpdateWithAnEmptyCategoryRemovesIt(): void
    {
        $project = $this->createProject()->setState($this->states[ProjectState::CODE_IN_PROGRESS])->setCategory($this->category);

        $this->createManager()->update($project, new ProjectPayload('Plateforme', null, null, ''));

        $this->assertNull($project->getCategory());
        // État non envoyé : inchangé
        $this->assertSame($this->states[ProjectState::CODE_IN_PROGRESS], $project->getState());
    }

    public function testUpdateChangesTheState(): void
    {
        $project = $this->createProject()->setState($this->states[ProjectState::CODE_IN_PROGRESS]);

        $this->createManager()->update($project, new ProjectPayload('Plateforme', null, ProjectState::CODE_DONE));

        $this->assertSame($this->states[ProjectState::CODE_DONE], $project->getState());
    }

    public function testSetFavorite(): void
    {
        $project = $this->createProject();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $this->createManager($entityManager)->setFavorite($project, true);

        $this->assertTrue($project->isFavorite());
    }

    public function testUnknownStateIsRefused(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Ce statut n\'existe pas.');

        $this->createManager()->create(new ProjectPayload('Plateforme', null, 'abandonne'));
    }

    /**
     * Tous les statuts ont été supprimés de la table : impossible de créer un projet.
     */
    public function testCreateWithoutAnyStateIsRefused(): void
    {
        $this->states = [];

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Aucun statut de projet n\'existe : ajoute-en un depuis la liste des projets (roue dentée › Statuts).');

        $this->createManager()->create(new ProjectPayload('Plateforme'));
    }

    /**
     * Un statut ajouté depuis la liste des projets (son code n'est pas un des trois de départ) est accepté.
     */
    public function testStateCreatedFromTheSettingsIsAccepted(): void
    {
        $this->states['en_pause'] = new ProjectState('en_pause', 'En pause', 'En pause', 3);

        $project = $this->createManager()->create(new ProjectPayload('Plateforme', null, 'en_pause'));

        $this->assertSame($this->states['en_pause'], $project->getState());
    }

    /* ------------------------------------------------------------------
       Logo long et avatar
       ------------------------------------------------------------------ */

    public function testCreateWithTheThreeImages(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(
            static function (Project $project): void {
                (new \ReflectionProperty(Project::class, 'id'))->setValue($project, 7);
            },
        );

        $project = $this->createManager($entityManager)->create(new ProjectPayload('Plateforme'), [
            'logo' => $this->createUploadedFile(),
            'logoWide' => $this->createUploadedFile(),
            'avatar' => $this->createUploadedFile(),
        ]);

        $this->assertStringStartsWith('logo-', (string) $project->getLogo());
        $this->assertStringStartsWith('logo-long-', (string) $project->getLogoWide());
        $this->assertStringStartsWith('avatar-', (string) $project->getAvatar());

        foreach (ProjectImage::cases() as $image) {
            $this->assertTrue($this->storage->exists($project, (string) $project->getImage($image)));
        }
    }

    public function testUpdateWithANewAvatarKeepsTheOtherImages(): void
    {
        $project = $this->createProject()->setLogo('logo.png')->setLogoWide('logo-long.png');
        $oldAvatar = $this->storage->store($project, $this->createUploadedFile(), 'avatar');
        $project->setAvatar($oldAvatar);

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), ['avatar' => $this->createUploadedFile()]);

        $this->assertNotSame($oldAvatar, $project->getAvatar());
        $this->assertFalse($this->storage->exists($project, $oldAvatar));
        $this->assertSame('logo.png', $project->getLogo());
        $this->assertSame('logo-long.png', $project->getLogoWide());
    }

    public function testUpdateRemovingTheLogoWideOnly(): void
    {
        $project = $this->createProject()->setLogo('logo.png')->setAvatar('avatar.png');
        $oldLogoWide = $this->storage->store($project, $this->createUploadedFile(), 'logo-long');
        $project->setLogoWide($oldLogoWide);

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), [], [ProjectImage::LogoWide]);

        $this->assertNull($project->getLogoWide());
        $this->assertFalse($this->storage->exists($project, $oldLogoWide));
        $this->assertSame('logo.png', $project->getLogo());
        $this->assertSame('avatar.png', $project->getAvatar());
    }

    /**
     * Une nouvelle image l'emporte sur la case « Supprimer » cochée en même temps.
     */
    public function testNewImageWinsOverRemoval(): void
    {
        $project = $this->createProject()->setAvatar('ancien.png');

        $this->createManager()->update($project, new ProjectPayload('Plateforme'), ['avatar' => $this->createUploadedFile()], [ProjectImage::Avatar]);

        $this->assertStringStartsWith('avatar-', (string) $project->getAvatar());
    }

    /* ------------------------------------------------------------------
       Suppression
       ------------------------------------------------------------------ */

    public function testDeleteRemovesTheProjectAndItsFolder(): void
    {
        $project = $this->createProject();
        $logo = $this->storage->store($project, $this->createUploadedFile(), 'logo');
        $project->setLogo($logo);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($project);
        // Comme Doctrine, le flush efface l'id du projet supprimé : le dossier doit quand même être trouvé
        $entityManager->expects($this->once())->method('flush')->willReturnCallback(
            static function () use ($project): void {
                (new \ReflectionProperty(Project::class, 'id'))->setValue($project, null);
            },
        );

        $this->createManager($entityManager)->delete($project);

        $this->assertDirectoryDoesNotExist($this->directory.'/42');
    }

    /**
     * Si la base refuse la suppression, les fichiers du projet ne sont pas effacés.
     */
    public function testDeleteKeepsTheFolderIfTheDatabaseFails(): void
    {
        $project = $this->createProject();
        $logo = $this->storage->store($project, $this->createUploadedFile(), 'logo');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('flush')->willThrowException(new \RuntimeException('Base indisponible'));

        try {
            $this->createManager($entityManager)->delete($project);
            $this->fail('L\'erreur de la base aurait dû remonter.');
        } catch (\RuntimeException) {
        }

        $this->assertTrue($this->storage->exists($project, $logo));
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
            $this->createStateRepository(),
            $this->createCategoryRepository(),
        );
    }

    /**
     * Faux repository des états : getByCode() renvoie toujours le même objet pour un même code.
     */
    private function createStateRepository(): ProjectStateRepository
    {
        $repository = $this->createStub(ProjectStateRepository::class);
        $repository->method('findFirst')->willReturnCallback(fn (): ?ProjectState => $this->states[ProjectState::CODE_NOT_STARTED] ?? null);
        $repository->method('findOneBy')->willReturnCallback(fn (array $criteria): ?ProjectState => $this->states[$criteria['code']] ?? null);

        return $repository;
    }

    /**
     * Faux repository des catégories : seule la catégorie n°1 (« Jeu ») existe.
     */
    private function createCategoryRepository(): ProjectCategoryRepository
    {
        $repository = $this->createStub(ProjectCategoryRepository::class);
        $repository->method('find')->willReturnCallback(fn (mixed $id): ?ProjectCategory => 1 === $id ? $this->category : null);

        return $repository;
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
