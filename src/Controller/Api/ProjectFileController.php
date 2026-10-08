<?php

namespace App\Controller\Api;

use App\Dto\ProjectFilePayload;
use App\Entity\Project;
use App\Entity\ProjectFile;
use App\Repository\ProjectFileRepository;
use App\Service\ProjectFileManager;
use App\Service\ProjectFilePreview;
use App\Validator\ProjectFileUpload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des fichiers d'un projet : /api/projet/{slug}/fichiers
 */
#[Route('/api/projet/{slug:project}/fichiers', name: 'api_project_files_', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
final class ProjectFileController extends AbstractController
{
    public function __construct(
        private readonly ProjectFileManager $projectFileManager,
        private readonly ProjectFilePreview $projectFilePreview,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Project $project, ProjectFileRepository $projectFileRepository): JsonResponse
    {
        return $this->json(array_map($this->serializeFile(...), $projectFileRepository->findByProjectNewestFirst($project)));
    }

    /**
     * Envoyé en multipart/form-data : le fichier (champ "file") + titre et description.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Project $project,
        #[MapRequestPayload] ProjectFilePayload $payload,
        #[MapUploadedFile(new ProjectFileUpload())] ?UploadedFile $file = null,
    ): JsonResponse {
        if (null === $file) {
            return $this->json(['detail' => 'Choisis un fichier à envoyer.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $projectFile = $this->projectFileManager->upload($project, $file, $payload);

        return $this->json($this->serializeFile($projectFile), Response::HTTP_CREATED);
    }

    /**
     * Modification du titre et de la description (le fichier lui-même ne change pas).
     */
    #[Route('/{id:file}', name: 'update', methods: ['PATCH'])]
    public function update(Project $project, ProjectFile $file, #[MapRequestPayload] ProjectFilePayload $payload): JsonResponse
    {
        $this->assertBelongsToProject($project, $file);

        try {
            $this->projectFileManager->update($file, $payload);
        } catch (\DomainException $exception) {
            return $this->json(['detail' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeFile($file));
    }

    #[Route('/{id:file}', name: 'delete', methods: ['DELETE'])]
    public function delete(Project $project, ProjectFile $file): JsonResponse
    {
        $this->assertBelongsToProject($project, $file);
        $this->projectFileManager->delete($file);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Un fichier n'est accessible que par l'URL de son propre projet.
     */
    private function assertBelongsToProject(Project $project, ProjectFile $file): void
    {
        if ($file->getProject() !== $project) {
            throw new NotFoundHttpException('Ce fichier n\'appartient pas à ce projet.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFile(ProjectFile $file): array
    {
        $routeParameters = ['slug' => $file->getProject()->getSlug(), 'id' => $file->getId()];

        return [
            'id' => $file->getId(),
            'type' => $file->getType()->value,
            'title' => $file->getTitle(),
            'description' => $file->getDescription(),
            'originalName' => $file->getOriginalName(),
            'extension' => $file->getExtension(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'createdAt' => $file->getCreatedAt()->format(\DATE_ATOM),
            // image, pdf, text, ou null si le navigateur ne sait pas l'afficher (Word, Excel…)
            'preview' => $this->projectFilePreview->getPreviewKind($file),
            'viewUrl' => $this->generateUrl('app_project_file', $routeParameters),
            'downloadUrl' => $this->generateUrl('app_project_file_download', $routeParameters),
        ];
    }
}
