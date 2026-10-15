<?php

namespace App\Twig;

use App\Entity\ProjectFile;
use App\Service\ProjectFilePreview;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Fonctions Twig des fichiers de projet.
 *
 *   {{ file_preview(file) }} → image, pdf, text, ou null si le navigateur ne sait pas l'afficher (Word, Excel…)
 *   {{ file_kind(file) }}    → famille du fichier pour sa couleur : pdf, word, excel, powerpoint, text ou other
 */
final class ProjectFileExtension extends AbstractExtension
{
    private const array KINDS = [
        'pdf' => 'pdf',
        'doc' => 'word',
        'docx' => 'word',
        'odt' => 'word',
        'xls' => 'excel',
        'xlsx' => 'excel',
        'ods' => 'excel',
        'csv' => 'excel',
        'ppt' => 'powerpoint',
        'pptx' => 'powerpoint',
        'odp' => 'powerpoint',
        'txt' => 'text',
        'md' => 'text',
    ];

    public function __construct(
        private readonly ProjectFilePreview $projectFilePreview,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_preview', $this->projectFilePreview->getPreviewKind(...)),
            new TwigFunction('file_kind', self::kind(...)),
        ];
    }

    public static function kind(ProjectFile $file): string
    {
        return self::KINDS[strtolower((string) $file->getExtension())] ?? 'other';
    }
}
