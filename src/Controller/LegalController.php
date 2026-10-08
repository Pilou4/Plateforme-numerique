<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages légales du site public (liens dans le pied de page).
 * Le contenu est dans les templates : templates/legal/.
 */
final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'legal_notice')]
    public function notice(): Response
    {
        return $this->render('legal/notice.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'privacy_policy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }
}
