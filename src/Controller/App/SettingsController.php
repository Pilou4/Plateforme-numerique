<?php

namespace App\Controller\App;

use App\Enum\ProjectStateColor;
use App\Repository\ProjectCategoryRepository;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page « Paramètres » : les listes utilisées par les projets, gérées depuis l'app
 * (catégories et statuts). L'ajout, la modification et la suppression passent par l'API
 * (Api\ProjectCategoryController, Api\ProjectStateController).
 */
final class SettingsController extends AbstractController
{
    #[Route('/app/parametres', name: 'app_settings')]
    public function index(
        ProjectCategoryRepository $projectCategoryRepository,
        ProjectStateRepository $projectStateRepository,
        ProjectRepository $projectRepository,
    ): Response {
        // Chaque ligne affiche le nombre de projets qui l'utilisent (aide avant une suppression)
        $categories = [];
        foreach ($projectCategoryRepository->findAllOrdered() as $category) {
            $categories[] = ['entity' => $category, 'projectCount' => $projectRepository->count(['category' => $category])];
        }

        $states = [];
        foreach ($projectStateRepository->findAllOrdered() as $state) {
            $states[] = ['entity' => $state, 'projectCount' => $projectRepository->count(['state' => $state])];
        }

        return $this->render('app/settings/index.html.twig', [
            'categories' => $categories,
            'states' => $states,
            'colors' => ProjectStateColor::cases(),
        ]);
    }
}
