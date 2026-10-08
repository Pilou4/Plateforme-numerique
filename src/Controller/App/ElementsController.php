<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page « Éléments » : catalogue des styles de base de l'app (bandeaux, icônes…),
 * pour voir le rendu de chaque élément. Elle sera complétée au fur et à mesure.
 */
final class ElementsController extends AbstractController
{
    #[Route('/app/elements', name: 'app_elements')]
    public function index(
        #[Autowire('%kernel.project_dir%/public/images/icones')]
        string $iconDirectory,
    ): Response {
        return $this->render('app/elements/index.html.twig', [
            'notice_types' => [
                'success' => 'Modification enregistrée.',
                'warning' => 'Le titre ne peut pas être vide.',
                'error' => 'Suppression impossible : erreur réseau.',
                'info' => 'Tableau trié : le glisser-déposer revient avec la colonne « # ».',
            ],
            'icons' => $this->findIcons($iconDirectory),
        ]);
    }

    /**
     * Toutes les icônes SVG du dossier, triées par nom :
     * une nouvelle icône apparaît sur la page sans rien modifier.
     *
     * @return list<string>
     */
    private function findIcons(string $iconDirectory): array
    {
        $icons = array_map('basename', glob($iconDirectory.'/*.svg') ?: []);
        sort($icons);

        return $icons;
    }
}
