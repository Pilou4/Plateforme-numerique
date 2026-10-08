<?php

namespace App\Controller\App;

use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Enum\ProjectStepPriority;
use App\Enum\ProjectStepStatus;
use App\Repository\ProjectRepository;
use App\Service\ProjectFileManager;
use App\Service\ProjectFilePreview;
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
 * /app/projet/{slug}/fichiers  → images et documents d'un projet
 * /app/projet/{slug}/fichiers/{id}              → un fichier, affiché dans le navigateur
 * /app/projet/{slug}/fichiers/{id}/telecharger  → un fichier, téléchargé
 */
#[Route('/app/projet', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
final class ProjectController extends AbstractController
{
    #[Route('', name: 'app_project')]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('app/project/index.html.twig', [
            'projects' => $projectRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/{slug:project}', name: 'app_project_show')]
    public function show(Project $project): Response
    {
        return $this->render('app/project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{slug:project}/taches', name: 'app_project_steps')]
    public function steps(Project $project): Response
    {
        // Les tâches sont chargées par le JavaScript via l'API ;
        // la page ne reçoit que le projet, les statuts et les priorités possibles.
        return $this->render('app/project/steps.html.twig', [
            'project' => $project,
            'statuses' => ProjectStepStatus::cases(),
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

    /**
     * Le logo est dans le stockage privé : il passe par ce contrôleur
     * (plus tard, c'est ici qu'on vérifiera les droits d'accès).
     */
    #[Route('/{slug:project}/logo', name: 'app_project_logo')]
    public function logo(Project $project, ProjectStorage $projectStorage): BinaryFileResponse
    {
        $logo = $project->getLogo();

        if (null === $logo || !$projectStorage->exists($project, $logo)) {
            throw new NotFoundHttpException('Ce projet n\'a pas de logo.');
        }

        $response = new BinaryFileResponse($projectStorage->getPath($project, $logo));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $logo);
        // Le navigateur doit respecter le type annoncé, sans le deviner
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Un SVG peut contenir du JavaScript : s'il est ouvert directement dans le navigateur,
        // cette règle l'empêche de s'exécuter (dans une balise <img>, il ne s'exécute jamais).
        if (str_ends_with($logo, '.svg')) {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox");
        }

        return $response;
    }
}
