<?php

namespace App\Service;

use App\Entity\ProjectFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Affichage et téléchargement d'un fichier de projet par le navigateur.
 *
 * Aperçus possibles :
 *   image → affichée dans une balise <img>
 *   pdf   → visionneuse PDF du navigateur
 *   text  → texte brut (TXT, Markdown)
 *   null  → pas d'aperçu (Word, Excel, PowerPoint) : téléchargement uniquement
 */
final class ProjectFilePreview
{
    public const string PREVIEW_IMAGE = 'image';
    public const string PREVIEW_PDF = 'pdf';
    public const string PREVIEW_TEXT = 'text';

    // Interdit tout script et tout chargement externe dans le fichier ouvert seul
    private const string SANDBOX_POLICY = "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox";

    public function __construct(
        private readonly ProjectFileManager $projectFileManager,
    ) {
    }

    public function getPreviewKind(ProjectFile $file): ?string
    {
        $mimeType = (string) $file->getMimeType();

        return match (true) {
            $file->isImage() => self::PREVIEW_IMAGE,
            'application/pdf' === $mimeType => self::PREVIEW_PDF,
            str_starts_with($mimeType, 'text/') => self::PREVIEW_TEXT,
            default => null,
        };
    }

    /**
     * @param bool $download true : « Enregistrer sous » ; false : affiché dans le navigateur si possible
     */
    public function createResponse(ProjectFile $file, bool $download): BinaryFileResponse
    {
        $preview = $this->getPreviewKind($file);
        $response = new BinaryFileResponse($this->projectFileManager->getPath($file));

        // Sans aperçu possible (Word…), le fichier est toujours téléchargé
        $disposition = $download || null === $preview ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE;
        $response->setContentDisposition($disposition, (string) $file->getOriginalName());

        // Le navigateur doit respecter le type enregistré, sans le deviner
        $response->headers->set('Content-Type', self::PREVIEW_TEXT === $preview ? 'text/plain; charset=UTF-8' : (string) $file->getMimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPrivate();

        // Un SVG ou un texte ouvert seul ne doit rien exécuter.
        // Pas pour le PDF : la visionneuse PDF du navigateur ne fonctionne pas dans un « bac à sable ».
        if ('image/svg+xml' === $file->getMimeType() || self::PREVIEW_TEXT === $preview) {
            $response->headers->set('Content-Security-Policy', self::SANDBOX_POLICY);
        }

        return $response;
    }
}
