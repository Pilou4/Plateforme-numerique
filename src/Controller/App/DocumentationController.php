<?php

namespace App\Controller\App;

use App\Entity\DocumentationTopic;
use App\Repository\DocumentationTopicRepository;
use App\Repository\GlossaryTermRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DocumentationController extends AbstractController
{
    #[Route('/app/documentation', name: 'app_documentation')]
    public function index(DocumentationTopicRepository $topicRepository): Response
    {
        return $this->render('app/documentation/index.html.twig', [
            'topics' => $topicRepository->findBy([], ['position' => 'ASC']),
        ]);
    }

    // Priorité 1 : cette route est testée avant /app/documentation/{slug},
    // sinon "glossaire" serait pris pour le slug d'un tutoriel.
    #[Route('/app/documentation/glossaire', name: 'app_documentation_glossary', priority: 1)]
    public function glossary(GlossaryTermRepository $glossaryTermRepository): Response
    {
        return $this->render('app/documentation/glossary.html.twig', [
            'terms' => $glossaryTermRepository->findBy([], ['term' => 'ASC']),
        ]);
    }

    // {slug:topic} : Symfony cherche le DocumentationTopic dont le slug correspond,
    // et renvoie une 404 s'il n'existe pas.
    #[Route('/app/documentation/{slug:topic}', name: 'app_documentation_show')]
    public function show(DocumentationTopic $topic): Response
    {
        return $this->render('app/documentation/show.html.twig', [
            'topic' => $topic,
        ]);
    }
}
