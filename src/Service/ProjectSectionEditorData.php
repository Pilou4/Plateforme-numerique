<?php

namespace App\Service;

use App\Entity\ProjectFile;
use App\Entity\ProjectSection;
use App\Entity\ProjectSectionBlock;
use App\Entity\ProjectSectionBlockFile;
use App\Enum\ProjectSectionBlockType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Données d'une section pour l'éditeur de blocs (JavaScript) : le même format que ce que
 * l'éditeur renvoie à l'API (voir ProjectSectionPayload), avec en plus, pour les fichiers,
 * ce qu'il faut pour les afficher (titre, extension, adresse de l'image).
 */
final class ProjectSectionEditorData
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array{id: int|null, title: string|null, blocks: list<array<string, mixed>>}
     */
    public function forSection(ProjectSection $section): array
    {
        return [
            'id' => $section->getId(),
            'title' => $section->getTitle(),
            'blocks' => array_values(array_map($this->forBlock(...), $section->getBlocks()->toArray())),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function forBlock(ProjectSectionBlock $block): array
    {
        $type = $block->getType();
        $data = ['type' => $type->value];

        return $data + match ($type) {
            ProjectSectionBlockType::Paragraph => [
                'text' => implode("\n", array_map(static fn ($paragraph): string => $paragraph->getText(), $block->getParagraphs()->toArray())),
            ],
            ProjectSectionBlockType::Subtitle => ['text' => (string) $block->getSubtitle()],
            ProjectSectionBlockType::List => [
                'ordered' => $block->isOrdered(),
                'items' => array_values(array_map(static fn ($item): string => $item->getText(), $block->getListItems()->toArray())),
            ],
            ProjectSectionBlockType::Table => [
                'columns' => array_values(array_map(static fn ($column): string => $column->getLabel(), $block->getTableColumns()->toArray())),
                'rows' => array_values(array_map(
                    static fn ($row): array => array_map(static fn ($cell): string => $cell->getText(), $row->getOrderedCells()),
                    $block->getTableRows()->toArray(),
                )),
            ],
            ProjectSectionBlockType::Tags => [
                'tags' => array_values(array_map(static fn ($tag): string => $tag->getLabel(), $block->getTags()->toArray())),
            ],
            ProjectSectionBlockType::Definitions => [
                'items' => array_values(array_map(
                    static fn ($definition): array => ['term' => $definition->getTerm(), 'definition' => $definition->getDefinition()],
                    $block->getDefinitions()->toArray(),
                )),
            ],
            ProjectSectionBlockType::Images, ProjectSectionBlockType::Documents => [
                'files' => array_values(array_map($this->forBlockFile(...), $block->getFiles()->toArray())),
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function forBlockFile(ProjectSectionBlockFile $link): array
    {
        return ['caption' => $link->getCaption()] + $this->forFile($link->getFile());
    }

    /**
     * Un fichier tel que l'éditeur l'affiche (même nom de champs que l'API des fichiers).
     *
     * @return array<string, mixed>
     */
    public function forFile(ProjectFile $file): array
    {
        $routeParameters = ['slug' => $file->getProject()?->getSlug(), 'id' => $file->getId()];

        return [
            'id' => $file->getId(),
            'type' => $file->getType()->value,
            'title' => $file->getTitle(),
            'extension' => $file->getExtension(),
            'size' => $file->getSize(),
            'viewUrl' => $this->urlGenerator->generate('app_project_file', $routeParameters),
        ];
    }
}
