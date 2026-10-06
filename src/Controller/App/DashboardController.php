<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/app/tableau-de-bord', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('app/dashboard/index.html.twig');
    }
}
