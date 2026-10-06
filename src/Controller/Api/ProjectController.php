<?php

namespace App\Controller\Api;

use App\Dto\ProjectPayload;
use App\Entity\Project;
use App\Service\ProjectManager;
use App\Validator\ProjectLogo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des projets : /api/projet
 */
#[Route('/api/projet', name: 'api_projects_')]
final class ProjectController extends AbstractController
{
    /**
     * Le formulaire est envoyé en multipart/form-data (à cause du logo) :
     * les champs texte sont lus par MapRequestPayload, le fichier par MapUploadedFile.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] ProjectPayload $payload,
        ProjectManager $projectManager,
        #[MapUploadedFile(new ProjectLogo())] ?UploadedFile $logo = null,
    ): JsonResponse {
        try {
            $project = $projectManager->create($payload, $logo);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeProject($project), Response::HTTP_CREATED);
    }

    /**
     * Modification d'un projet. En POST et non en PATCH : PHP ne lit pas
     * les formulaires multipart/form-data (avec fichier) envoyés en PATCH.
     */
    #[Route('/{slug:project}', name: 'update', requirements: ['slug' => '[a-z0-9-]+'], methods: ['POST'])]
    public function update(
        Project $project,
        Request $request,
        #[MapRequestPayload] ProjectPayload $payload,
        ProjectManager $projectManager,
        #[MapUploadedFile(new ProjectLogo())] ?UploadedFile $logo = null,
    ): JsonResponse {
        try {
            $projectManager->update($project, $payload, $logo, $request->request->getBoolean('removeLogo'));
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeProject($project));
    }

    /**
     * L'URL est renvoyée car le slug peut changer avec le nom.
     *
     * @return array{id: int|null, name: string|null, slug: string|null, url: string}
     */
    private function serializeProject(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'slug' => $project->getSlug(),
            'url' => $this->generateUrl('app_project_show', ['slug' => $project->getSlug()]),
        ];
    }
}
