<?php

namespace App\Controller;

use App\Content\ServiceCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ServiceCatalog $serviceCatalog): Response
    {
        return $this->render('home/index.html.twig', [
            // Les cartes « Services » de l'accueil mènent aux pages de chaque service
            'services' => $serviceCatalog->findAll(),
        ]);
    }
}
