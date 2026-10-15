<?php

namespace App\Controller\App;

use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Enum\ProjectImage;
use App\Enum\ProjectStepPriority;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStateRepository;
use App\Repository\ProjectStatusRepository;
use App\Service\ProjectFileManager;
use App\Service\ProjectFilePreview;
use App\Service\ProjectSectionEditorData;
use App\Service\ProjectStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * /app/projet                  → liste des projets
 * /app/projet/{slug}           → présentation d'un projet
 * /app/projet/{slug}/taches    → tâches d'un projet
 * /app/projet/{slug}/logo      → image du logo (lue dans le stockage privé)
 * /app/projet/{slug}/logo-long → image du logo long
 * /app/projet/{slug}/avatar    → image de l'avatar
 * /app/projet/{slug}/fichiers  → images et documents d'un projet
 * /app/projet/{slug}/fichiers/{id}              → un fichier, affiché dans le navigateur
 * /app/projet/{slug}/fichiers/{id}/telecharger  → un fichier, téléchargé
 */
#[Route('/app/projet', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
final class ProjectController extends AbstractController
{
    #[Route('', name: 'app_project')]
    public function index(
        ProjectRepository $projectRepository,
        ProjectStateRepository $projectStateRepository,
        ProjectCategoryRepository $projectCategoryRepository,
    ): Response {
        return $this->render('app/project/index.html.twig', [
            'projects' => $projectRepository->findBy([], ['name' => 'ASC']),
            // Filtres de la liste et listes déroulantes du formulaire
            'states' => $projectStateRepository->findAllOrdered(),
            'categories' => $projectCategoryRepository->findAllOrdered(),
        ]);
    }

    #[Route('/{slug:project}', name: 'app_project_show')]
    public function show(
        Project $project,
        ProjectStateRepository $projectStateRepository,
        ProjectCategoryRepository $projectCategoryRepository,
        ProjectSectionEditorData $projectSectionEditorData,
    ): Response {
        $sectionsEditorData = [];
        foreach ($project->getSections() as $section) {
            $sectionsEditorData[$section->getId()] = $projectSectionEditorData->forSection($section);
        }

        return $this->render('app/project/show.html.twig', [
            'project' => $project,
            // Contenu de chaque section pour l'éditeur de blocs (JavaScript), par id de section
            'sections_editor_data' => $sectionsEditorData,
            // Listes déroulantes du formulaire de modification
            'states' => $projectStateRepository->findAllOrdered(),
            'categories' => $projectCategoryRepository->findAllOrdered(),
        ]);
    }

    #[Route('/{slug:project}/taches', name: 'app_project_steps')]
    public function steps(Project $project, ProjectStatusRepository $projectStatusRepository): Response
    {
        // Les tâches sont chargées par le JavaScript via l'API ;
        // la page ne reçoit que le projet, les statuts et les priorités possibles.
        return $this->render('app/project/steps.html.twig', [
            'project' => $project,
            'statuses' => $projectStatusRepository->findAllOrdered(),
            'priorities' => ProjectStepPriority::cases(),
        ]);
    }

    #[Route('/{slug:project}/fichiers', name: 'app_project_files')]
    public function files(Project $project): Response
    {
        // Les fichiers sont chargés par le JavaScript via l'API
        return $this->render('app/project/files.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{slug:project}/fichiers/{id:file}', name: 'app_project_file')]
    public function showFile(Project $project, ProjectFile $file, ProjectFileManager $projectFileManager, ProjectFilePreview $projectFilePreview): BinaryFileResponse
    {
        $this->assertFileIsAvailable($project, $file, $projectFileManager);

        return $projectFilePreview->createResponse($file, download: false);
    }

    #[Route('/{slug:project}/fichiers/{id:file}/telecharger', name: 'app_project_file_download')]
    public function downloadFile(Project $project, ProjectFile $file, ProjectFileManager $projectFileManager, ProjectFilePreview $projectFilePreview): BinaryFileResponse
    {
        $this->assertFileIsAvailable($project, $file, $projectFileManager);

        return $projectFilePreview->createResponse($file, download: true);
    }

    /**
     * Le fichier doit appartenir au projet de l'URL et exister sur le disque.
     */
    private function assertFileIsAvailable(Project $project, ProjectFile $file, ProjectFileManager $projectFileManager): void
    {
        if ($file->getProject() !== $project || !$projectFileManager->exists($file)) {
            throw new NotFoundHttpException('Ce fichier est introuvable.');
        }
    }

    #[Route('/{slug:project}/logo', name: 'app_project_logo')]
    public function logo(Project $project, ProjectStorage $projectStorage): BinaryFileResponse
    {
        return $this->createImageResponse($project, ProjectImage::Logo, $projectStorage);
    }

    #[Route('/{slug:project}/logo-long', name: 'app_project_logo_wide')]
    public function logoWide(Project $project, ProjectStorage $projectStorage): BinaryFileResponse
    {
        return $this->createImageResponse($project, ProjectImage::LogoWide, $projectStorage);
    }

    #[Route('/{slug:project}/avatar', name: 'app_project_avatar')]
    public function avatar(Project $project, ProjectStorage $projectStorage): BinaryFileResponse
    {
        return $this->createImageResponse($project, ProjectImage::Avatar, $projectStorage);
    }

    /**
     * Les images sont dans le stockage privé : elles passent par ce contrôleur
     * (plus tard, c'est ici qu'on vérifiera les droits d'accès).
     */
    private function createImageResponse(Project $project, ProjectImage $image, ProjectStorage $projectStorage): BinaryFileResponse
    {
        $fileName = $project->getImage($image);

        if (null === $fileName || !$projectStorage->exists($project, $fileName)) {
            throw new NotFoundHttpException(\sprintf('Ce projet n\'a pas d\'image « %s ».', mb_strtolower($image->label())));
        }

        $response = new BinaryFileResponse($projectStorage->getPath($project, $fileName));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);
        // Le navigateur doit respecter le type annoncé, sans le deviner
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Un SVG peut contenir du JavaScript : s'il est ouvert directement dans le navigateur,
        // cette règle l'empêche de s'exécuter (dans une balise <img>, il ne s'exécute jamais).
        if (str_ends_with($fileName, '.svg')) {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox");
        }

        return $response;
    }
}
