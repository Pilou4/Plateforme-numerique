<?php

namespace App\Controller;

use App\Content\ServiceCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Site public : /services (liste) et /services/{slug} (un service).
 * Le contenu est dans App\Content\ServiceCatalog.
 */
#[Route('/services')]
final class ServicesController extends AbstractController
{
    public function __construct(
        private readonly ServiceCatalog $serviceCatalog,
    ) {
    }

    #[Route('', name: 'services')]
    public function index(): Response
    {
        return $this->render('services/index.html.twig', [
            'services' => $this->serviceCatalog->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'services_show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug): Response
    {
        $service = $this->serviceCatalog->findBySlug($slug);

        if (null === $service) {
            throw new NotFoundHttpException('Ce service n\'existe pas.');
        }

        return $this->render('services/show.html.twig', [
            'service' => $service,
            // Les autres services, proposés en bas de page
            'other_services' => array_values(array_filter(
                $this->serviceCatalog->findAll(),
                static fn (array $item): bool => $item['slug'] !== $slug,
            )),
        ]);
    }
}
