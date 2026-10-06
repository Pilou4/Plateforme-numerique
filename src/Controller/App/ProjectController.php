<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectController extends AbstractController
{
    #[Route('/app/projet', name: 'app_project')]
    public function index(): Response
    {
        return $this->render('app/project/index.html.twig');
    }

    #[Route('/app/projet/etapes', name: 'app_project_steps')]
    public function steps(): Response
    {
        return $this->render('app/project/steps.html.twig');
    }
}
